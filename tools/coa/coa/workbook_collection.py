"""Keep all XLSX workbooks from browser-downloaded COA ZIPs in batches of 15."""

import argparse
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import re
import shutil
import stat
import sys
from tempfile import TemporaryDirectory
from urllib.parse import unquote, urlsplit
import zipfile

from openpyxl import load_workbook

from .collection import Collection, collectionLock, fingerprint, listedSizeMatches, watchDownloads

BATCH_SIZE = 15


def checksum(path):
    with path.open("rb") as handle:
        return hashlib.file_digest(handle, "sha256").hexdigest()


def safeParts(name):
    normalized = name.replace("\\", "/")
    path = PurePosixPath(normalized)
    if (path.is_absolute() or not path.parts or ".." in path.parts
            or any(re.search(r'[<>:"|?*\x00-\x1f]', part) or part.endswith((".", " "))
                   or re.fullmatch(r"(?:CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\..*)?", part, re.I)
                   for part in path.parts)):
        raise ValueError(f"Unsafe archive path: {name}")
    return path.parts


def validateWorkbook(path, maxBytes):
    with zipfile.ZipFile(path) as workbook:
        if sum(member.file_size for member in workbook.infolist()) > maxBytes:
            raise ValueError("Workbook exceeds the configured extraction limit")
        bad = workbook.testzip()
        if bad:
            raise ValueError(f"Workbook CRC failure: {bad}")
    workbook = load_workbook(path, read_only=True, data_only=False, keep_links=False)
    try:
        if not workbook.worksheets:
            raise ValueError("Workbook has no worksheets")
        for sheet in workbook.worksheets:
            # Ignore incorrect declared dimensions so every stored row is checked.
            sheet.reset_dimensions()
            for _ in sheet.iter_rows(values_only=True):
                pass
    finally:
        workbook.close()


class WorkbookCollection(Collection):
    batchSize = BATCH_SIZE
    extension = ".zip"

    def __init__(self, inventoryPath, statePath, output, maxMembers, maxBytes, workers=4):
        super().__init__(inventoryPath, statePath, workers)
        self.output = Path(output).resolve()
        self.maxMembers = maxMembers
        self.maxBytes = maxBytes
        if min(maxMembers, maxBytes) <= 0:
            raise ValueError("Extraction limits must be positive")
        for item in self.items.values():
            if not re.fullmatch(r".+ Annual Audit Report 20\d{2}", item["title"].strip(), re.I):
                raise ValueError("Inventory must identify an Annual Audit Report and year")
            if not re.fullmatch(r"02015\d{5}", item.get("psgcCode", "")):
                raise ValueError("Inventory must identify a Cagayan PSGC code")
            if len(safeParts(item["townFolder"])) != 1:
                raise ValueError("Municipality folder must be a single safe name")
            if len(safeParts(item["fileName"])) != 1 or not item["fileName"].lower().endswith(".zip"):
                raise ValueError("Inventory filename must be a ZIP basename")
            if not re.fullmatch(r"[A-Za-z0-9_-]+", str(item["id"])):
                raise ValueError("Unsafe inventory ID")

    def retained(self, entry):
        if entry.get("output") != str(self.output):
            return False
        for workbook in entry.get("workbooks", []):
            path = Path(workbook["path"])
            try:
                if not path.resolve().is_relative_to(self.output) or fingerprint(path) != workbook["fingerprint"]:
                    return False
            except OSError:
                return False
        return True

    def downloadReady(self, path):
        # Some browser saves write directly to .zip without a partial-file suffix.
        return zipfile.is_zipfile(path)

    def completed(self, fileId, path, retryFailed=False, recheck=False):
        old = self.entries.get(str(fileId), {})
        if (old.get("status") == "verified" and old.get("item") == self.items[str(fileId)]
                and not Path(path).exists() and not recheck and self.retained(old)):
            return
        super().completed(fileId, path, retryFailed, recheck)

    def validate(self, entry):
        path = Path(entry["downloadPath"])
        item = entry["item"]
        url = urlsplit(item["url"])
        if (url.scheme != "https" or url.hostname not in {"coa.gov.ph", "www.coa.gov.ph"}
                or url.username or url.password or url.port not in {None, 443}
                or not unquote(url.path).lower().endswith(".zip")):
            raise ValueError("Not an official HTTPS COA ZIP URL")
        before = fingerprint(path)
        if before != entry["fingerprint"] or not listedSizeMatches(before[0], item):
            raise ValueError("File changed or does not match the listed byte size")
        digest = checksum(path)
        if item.get("sha256") and digest != item["sha256"]:
            raise ValueError("Recorded SHA-256 mismatch")
        year = int(item["title"].strip()[-4:])
        folder = self.output / str(year) / item["townFolder"] / f'{item["id"]}-{digest[:16]}'
        self.output.mkdir(parents=True, exist_ok=True)
        with zipfile.ZipFile(path) as archive, TemporaryDirectory(prefix=".xlsx-", dir=self.output) as temporary:
            members = archive.infolist()
            if len(members) > self.maxMembers or sum(member.file_size for member in members) > self.maxBytes:
                raise ValueError("Archive exceeds the configured extraction limits")
            selected = []
            seen = set()
            for member in members:
                parts = safeParts(member.filename)
                if stat.S_ISLNK(member.external_attr >> 16):
                    raise ValueError(f"Archive contains a symbolic link: {member.filename}")
                if member.is_dir() or PurePosixPath(*parts).suffix.lower() != ".xlsx":
                    continue
                identity = "/".join(parts).casefold()
                if identity in seen:
                    raise ValueError(f"Duplicate workbook destination: {member.filename}")
                seen.add(identity)
                target = folder.joinpath(*parts)
                if not target.resolve().is_relative_to(self.output):
                    raise ValueError("Workbook destination escapes output folder")
                staged = Path(temporary).joinpath(*parts)
                staged.parent.mkdir(parents=True, exist_ok=True)
                with archive.open(member) as source, staged.open("xb") as destination:
                    shutil.copyfileobj(source, destination)
                    destination.flush()
                    os.fsync(destination.fileno())
                validateWorkbook(staged, self.maxBytes)
                selected.append((member, staged, target, checksum(staged)))
            bad = archive.testzip()
            if bad:
                raise ValueError(f"Archive CRC failure: {bad}")
            if fingerprint(path) != before:
                raise ValueError("Archive changed during extraction")
            workbooks = []
            for member, staged, target, workbookHash in selected:
                target.parent.mkdir(parents=True, exist_ok=True)
                try:
                    # Windows rename refuses existing targets; POSIX links do likewise.
                    if os.name == "nt":
                        staged.rename(target)
                    else:
                        os.link(staged, target)
                except FileExistsError:
                    pass
                if checksum(target) != workbookHash:
                    raise ValueError(f"Refusing to replace a different existing workbook: {target.name}")
                workbooks.append({"archiveMember": member.filename, "path": str(target),
                                  "bytes": target.stat().st_size, "sha256": workbookHash,
                                  "fingerprint": fingerprint(target)})
        # Removing staging hard links changes ctime on some filesystems.
        for workbook in workbooks:
            workbook["fingerprint"] = fingerprint(Path(workbook["path"]))
        return {"sha256": digest, "bytes": before[0], "zipVerified": True, "workbooks": workbooks,
                "outcome": "workbooks" if workbooks else "no_xlsx", "output": str(self.output),
                "archiveDeleted": False,
                "listedSizeCheck": "exact_bytes" if item.get("size") is not None else "rounded_display"}

    def cleanup(self, entry):
        path = Path(entry["downloadPath"])
        fields = {key: value for key, value in entry.items() if key not in {"id", "status", "at", "cleanupError"}}
        try:
            if not self.retained(entry):
                raise ValueError("Retained workbooks changed; archive preserved")
            if path.exists():
                if fingerprint(path) != entry["fingerprint"] or checksum(path) != entry["sha256"]:
                    raise ValueError("Archive changed after validation; archive preserved")
                path.unlink()
            self.save(entry["id"], "verified", **{**fields, "archiveDeleted": True})
        except (OSError, ValueError) as error:
            self.save(entry["id"], "verified", **fields, cleanupError=str(error))

    def check(self):
        super().check()
        for entry in list(self.entries.values()):
            if (entry["status"] == "verified" and entry.get("item") == self.items.get(entry["id"])
                    and not entry.get("archiveDeleted")):
                self.cleanup(entry)
        self.export()

    def manifestEntry(self, entry, year):
        return {**entry["item"], "fiscalYear": year,
                **{key: entry.get(key) for key in ("sha256", "bytes", "workbooks", "outcome", "archiveDeleted", "cleanupError", "at")}}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--inventory", type=Path, required=True)
    parser.add_argument("--state", type=Path, required=True)
    parser.add_argument("--output", type=Path, required=True)
    parser.add_argument("--members", type=int, required=True)
    parser.add_argument("--bytes", type=int, required=True)
    parser.add_argument("--workers", type=int, choices=range(1, 5), default=4)
    commands = parser.add_subparsers(dest="command", required=True)
    completed = commands.add_parser("completed")
    completed.add_argument("id")
    completed.add_argument("path", type=Path)
    failed = commands.add_parser("failed")
    failed.add_argument("id")
    failed.add_argument("error")
    scan = commands.add_parser("scan")
    scan.add_argument("--downloads", type=Path, required=True)
    scan.add_argument("--retry-failed", action="store_true")
    watch = commands.add_parser("watch")
    watch.add_argument("--downloads", type=Path, required=True)
    watch.add_argument("--finish-file", type=Path)
    watch.add_argument("--idle-seconds", type=float, default=120)
    watch.add_argument("--poll-seconds", type=float, default=0.5)
    commands.add_parser("flush")
    commands.add_parser("status")
    args = parser.parse_args()
    try:
        with collectionLock(args.state):
            collection = WorkbookCollection(args.inventory, args.state, args.output, args.members, args.bytes, args.workers)
            if args.command == "completed":
                collection.completed(args.id, args.path, retryFailed=True)
            elif args.command == "failed":
                collection.failed(args.id, args.error)
            elif args.command == "scan":
                for fileId, item in collection.items.items():
                    collection.completed(fileId, args.downloads / item["fileName"], args.retry_failed)
                collection.check()
            elif args.command == "watch":
                if args.idle_seconds <= 0 or args.poll_seconds <= 0:
                    raise ValueError("Watcher intervals must be positive")
                watchDownloads(collection, args.downloads.resolve(), args.finish_file, args.idle_seconds, args.poll_seconds)
            elif args.command == "flush":
                collection.check()
            if args.command not in {"completed", "failed"}:
                collection.export()
            entries = [entry for fileId, entry in collection.entries.items() if entry.get("item") == collection.items.get(fileId)]
            counts = {status: sum(entry["status"] == status for entry in entries) for status in ("verified", "failed", "pending")}
            counts["cleanupFailures"] = sum(bool(entry.get("cleanupError")) for entry in entries)
            counts["unrecorded"] = len(collection.items) - len(entries)
            print(json.dumps(counts), flush=True)
            return 1 if counts["failed"] or counts["cleanupFailures"] else 0
    except (OSError, ValueError, KeyError, TypeError) as error:
        print(str(error), file=sys.stderr)
        return 2


if __name__ == "__main__":
    sys.exit(main())
