"""Checkpoint browser downloads and check local PDFs in batches. No network access."""

import argparse
from concurrent.futures import ThreadPoolExecutor
from contextlib import contextmanager
from datetime import datetime, timezone
import hashlib
import json
import os
from pathlib import Path
import re
import sys
import time
import unicodedata
from urllib.parse import unquote, urlsplit

import pdfplumber
from pdfminer.pdftypes import resolve1

BATCH_SIZE = 12
WORKERS = 4


def timestamp():
    return datetime.now(timezone.utc).isoformat()


def fingerprint(path):
    stat = path.stat()
    return [stat.st_size, stat.st_mtime_ns, stat.st_ctime_ns]


def writeJson(path, value):
    path.parent.mkdir(parents=True, exist_ok=True)
    temporary = path.with_suffix(".tmp")
    temporary.write_text(json.dumps(value, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    for attempt in range(6):
        try:
            temporary.replace(path)
            return
        except PermissionError:
            if attempt == 5:
                raise
            # Windows readers can briefly lock the previous manifest during replacement.
            time.sleep(0.05 * (attempt + 1))


def listedSizeMatches(size, item):
    if item.get("size") is not None:
        return size == int(item["size"])
    match = re.fullmatch(r"(\d+(?:\.\d+)?)\s*(B|KB|MB|GB)", item.get("sizeLabel", ""), re.I)
    if not match or float(match[1]) <= 0:
        raise ValueError("Inventory requires an exact byte size or a COA size display")
    decimals = len(match[1].split(".")[1]) if "." in match[1] else 0
    unit = 1024 ** ("B", "KB", "MB", "GB").index(match[2].upper())
    return abs(size / unit - float(match[1])) <= 10 ** -decimals


def browserName(name):
    asciiName = unicodedata.normalize("NFKD", name).encode("ascii", "ignore").decode().casefold()
    return re.sub(r"[^a-z0-9]", "", asciiName)


@contextmanager
def collectionLock(statePath):
    """OS locks are released even if a collector crashes."""
    statePath.parent.mkdir(parents=True, exist_ok=True)
    with statePath.with_suffix(".lock").open("a+b") as handle:
        if handle.tell() == 0:
            handle.write(b"0")
            handle.flush()
        handle.seek(0)
        if os.name == "nt":
            import msvcrt
            msvcrt.locking(handle.fileno(), msvcrt.LK_NBLCK, 1)
        else:
            import fcntl
            fcntl.flock(handle.fileno(), fcntl.LOCK_EX | fcntl.LOCK_NB)
        try:
            yield
        finally:
            handle.seek(0)
            if os.name == "nt":
                msvcrt.locking(handle.fileno(), msvcrt.LK_UNLCK, 1)
            else:
                fcntl.flock(handle.fileno(), fcntl.LOCK_UN)


def validatePdf(entry):
    path = Path(entry["downloadPath"])
    item = entry["item"]
    url = urlsplit(item["url"])
    if (url.scheme != "https" or url.hostname not in {"coa.gov.ph", "www.coa.gov.ph"}
            or url.username or url.password or url.port not in {None, 443}
            or not unquote(url.path).lower().endswith(".pdf")):
        raise ValueError("Not an official HTTPS COA PDF URL")
    if not re.fullmatch(r".+ Executive Summary 20\d{2}", item["title"].strip(), re.I):
        raise ValueError("Not an Executive Summary with a fiscal year")
    before = fingerprint(path)
    if before != entry["fingerprint"] or not listedSizeMatches(before[0], item):
        raise ValueError("File changed or does not match the listed byte size")
    with path.open("rb") as handle:
        if b"%PDF-" not in handle.read(1024):
            raise ValueError("Missing PDF header")
        handle.seek(max(0, before[0] - 4096))
        if b"%%EOF" not in handle.read():
            raise ValueError("Missing PDF EOF marker")
        handle.seek(0)
        digest = hashlib.file_digest(handle, "sha256").hexdigest()
    if item.get("sha256") and digest != item["sha256"]:
        raise ValueError("Recorded SHA-256 mismatch")
    with pdfplumber.open(path) as document:
        if not document.pages:
            raise ValueError("PDF has no pages")
        for page in document.pages:
            if page.width <= 0 or page.height <= 0:
                raise ValueError("Invalid PDF page bounds")
            # Decode every content stream without expensive text/layout extraction.
            for stream in page.page_obj.contents:
                resolve1(stream).get_data()
        pages = len(document.pages)
    if fingerprint(path) != before:
        raise ValueError("File changed during validation")
    return {"sha256": digest, "bytes": before[0], "pages": pages, "pdfVerified": True,
            "listedSizeCheck": "exact_bytes" if item.get("size") is not None else "rounded_display"}


class Collection:
    def __init__(self, inventoryPath, statePath, workers=WORKERS, manifestRoot=None):
        self.statePath = Path(statePath)
        self.workers = workers
        self.manifestRoot = Path(manifestRoot) if manifestRoot else self.statePath.parent / (self.statePath.stem + "-manifests")
        self.items = {}
        inventory = json.loads(Path(inventoryPath).read_text(encoding="utf-8-sig"))
        if not isinstance(inventory, list):
            raise ValueError("Inventory must be a JSON array")
        for item in inventory:
            required = {"id", "title", "url", "fileName"}
            if not isinstance(item, dict) or not required.issubset(item):
                raise ValueError("Inventory entries require id, title, url, size and fileName")
            if not all(isinstance(item[key], str) for key in ("title", "url", "fileName")):
                raise ValueError("Inventory title, url and fileName must be strings")
            if item.get("size") is None:
                listedSizeMatches(0, item)
            elif int(item["size"]) <= 0:
                raise ValueError("Inventory size must be positive")
            fileId = str(item["id"])
            if fileId in self.items:
                raise ValueError(f"Duplicate inventory ID: {fileId}")
            self.items[fileId] = item
        self.entries = {}
        self.statePath.parent.mkdir(parents=True, exist_ok=True)
        if self.statePath.exists():
            # An interrupted final append is discarded; earlier corruption is an error.
            with self.statePath.open("r+b") as handle:
                while True:
                    start = handle.tell()
                    line = handle.readline()
                    if not line:
                        break
                    if not line.endswith(b"\n"):
                        handle.truncate(start)
                        break
                    event = json.loads(line)
                    self.entries[event["id"]] = event

    def save(self, fileId, status, **fields):
        event = {"id": str(fileId), "status": status, "at": timestamp(), **fields}
        with self.statePath.open("ab") as handle:
            handle.write((json.dumps(event, ensure_ascii=False) + "\n").encode("utf-8"))
            handle.flush()
            os.fsync(handle.fileno())
        self.entries[str(fileId)] = event

    def completed(self, fileId, path, retryFailed=False, recheck=False):
        fileId = str(fileId)
        item = self.items[fileId]
        path = Path(path).resolve()
        old = self.entries.get(fileId, {})
        try:
            if path.suffix.lower() != ".pdf":
                raise ValueError("Downloaded file must have a .pdf extension")
            signature = fingerprint(path)
        except (OSError, ValueError) as error:
            self.failed(fileId, str(error), path)
            return
        unchanged = (old.get("item") == item and old.get("downloadPath") == str(path)
                     and old.get("fingerprint") == signature)
        if unchanged and not recheck and (old["status"] != "failed" or not retryFailed):
            return
        self.save(fileId, "pending", item=item, downloadPath=str(path), fingerprint=signature)
        if len(self.pending()) >= BATCH_SIZE:
            self.check()

    def failed(self, fileId, error, path=None):
        item = self.items[str(fileId)]
        self.save(fileId, "failed", item=item, stage="download", error=error,
                  downloadPath=str(path) if path else None)

    def pending(self):
        return [entry for fileId, entry in self.entries.items()
                if entry["status"] == "pending" and entry.get("item") == self.items.get(fileId)]

    def check(self):
        pending = self.pending()
        for offset in range(0, len(pending), BATCH_SIZE):
            batch = pending[offset:offset + BATCH_SIZE]
            with ThreadPoolExecutor(max_workers=self.workers) as pool:
                futures = [(entry, pool.submit(validatePdf, entry)) for entry in batch]
                for entry, future in futures:
                    fields = {key: entry[key] for key in ("item", "downloadPath", "fingerprint")}
                    try:
                        metadata = future.result()
                    except Exception as error:
                        self.save(entry["id"], "failed", **fields, stage="validation",
                                  error=f"{type(error).__name__}: {error}")
                    else:
                        self.save(entry["id"], "verified", **fields, **metadata)
        self.export()

    def export(self):
        entries = [entry for fileId, entry in self.entries.items()
                   if entry.get("item") == self.items.get(fileId)]
        report = {"batchSize": BATCH_SIZE, "inventoryCount": len(self.items),
                  "verified": [entry for entry in entries if entry["status"] == "verified"],
                  "failures": [entry for entry in entries if entry["status"] == "failed"],
                  "pending": [entry["id"] for entry in entries if entry["status"] == "pending"],
                  "unrecorded": sorted(set(self.items) - {entry["id"] for entry in entries})}
        writeJson(self.statePath.with_suffix(".report.json"), report)
        years = {}
        for item in self.items.values():
            match = re.search(r"(20\d{2})$", item["title"].strip())
            if match:
                years.setdefault(int(match[1]), []).append(item)
        for year, items in years.items():
            folder = self.manifestRoot / str(year)
            writeJson(folder / "inventory.json", items)
            manifest = []
            fileIds = {str(item["id"]) for item in items}
            for entry in report["verified"]:
                if entry["id"] not in fileIds:
                    continue
                manifest.append({**entry["item"], "downloadPath": entry["downloadPath"],
                                 "fiscalYear": year, **{key: entry[key] for key in ("bytes", "sha256", "pages", "pdfVerified")}})
            writeJson(folder / "manifest.json", manifest)
        return report


def observeDownloads(collection, downloads, observed, final=False):
    """Recognize renamed, stable PDFs while ignoring browser partial files."""
    changed = False
    names = {}
    for candidate in downloads.glob("*.pdf"):
        normalized = browserName(candidate.name)
        names.setdefault(normalized, []).append(candidate)
    for fileId, item in collection.items.items():
        name = item["fileName"]
        if Path(name).name != name or "/" in name or "\\" in name:
            if fileId not in collection.entries:
                collection.failed(fileId, "Inventory filename must be a basename")
            continue
        path = downloads / name
        if not path.exists():
            normalized = browserName(name)
            candidates = names.get(normalized, [])
            if len(candidates) == 1:
                path = candidates[0]
        partial = any(path.with_name(name + suffix).exists() for suffix in (".crdownload", ".part", ".download"))
        if not path.is_file() or partial:
            observed.pop(fileId, None)
            continue
        try:
            signature = fingerprint(path)
        except OSError:
            continue
        if not final and observed.get(fileId) != signature:
            observed[fileId] = signature
            changed = True
            continue
        old = collection.entries.get(fileId)
        collection.completed(fileId, path)
        changed = changed or collection.entries.get(fileId) is not old
    return changed


def watchDownloads(collection, downloads, finishFile=None, idleSeconds=120, pollSeconds=0.5):
    if not downloads.is_dir():
        raise ValueError("Browser download folder does not exist")
    if finishFile and finishFile.exists():
        raise ValueError("Use a fresh finish marker for this browser run")
    observed = {}
    lastActivity = time.monotonic()
    print(json.dumps({"watching": str(downloads), "batchSize": BATCH_SIZE}), flush=True)
    try:
        while True:
            if observeDownloads(collection, downloads, observed):
                lastActivity = time.monotonic()
            finished = bool(finishFile and finishFile.exists())
            if finished or time.monotonic() - lastActivity >= idleSeconds:
                observeDownloads(collection, downloads, observed, final=True)
                for fileId, item in collection.items.items():
                    if collection.entries.get(fileId, {}).get("item") != item:
                        collection.failed(fileId, "No completed PDF observed before browser collection ended")
                break
            time.sleep(pollSeconds)
    except KeyboardInterrupt:
        print("Watcher interrupted; recorded downloads remain resumable", file=sys.stderr)
    finally:
        collection.check()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--inventory", type=Path, required=True)
    parser.add_argument("--state", type=Path, required=True, help="Append-only JSONL checkpoint journal")
    parser.add_argument("--workers", type=int, choices=range(1, 5), default=WORKERS)
    parser.add_argument("--manifest-root", type=Path, help="Per-year inventory/manifest output folder")
    commands = parser.add_subparsers(dest="command", required=True)
    completed = commands.add_parser("completed", help="Call immediately after each browser download")
    completed.add_argument("id")
    completed.add_argument("path", type=Path)
    failed = commands.add_parser("failed", help="Record a browser error and continue the download queue")
    failed.add_argument("id")
    failed.add_argument("error")
    scan = commands.add_parser("scan", help="Resume existing local downloads, then flush the last batch")
    scan.add_argument("--downloads", type=Path, required=True)
    scan.add_argument("--retry-failed", action="store_true")
    scan.add_argument("--recheck", action="store_true", help="Bypass the unchanged-file validation cache")
    watch = commands.add_parser("watch", help="Automatically checkpoint completed browser saves and check every 12")
    watch.add_argument("--downloads", type=Path, required=True)
    watch.add_argument("--finish-file", type=Path, help="Browser controller creates this marker after the last download")
    watch.add_argument("--idle-seconds", type=float, default=120)
    watch.add_argument("--poll-seconds", type=float, default=0.5)
    commands.add_parser("flush", help="Check the final partial batch")
    commands.add_parser("status", help="Rebuild the report from the durable journal")
    args = parser.parse_args()
    try:
        with collectionLock(args.state):
            collection = Collection(args.inventory, args.state, args.workers, args.manifest_root)
            if args.command == "completed":
                collection.completed(args.id, args.path, retryFailed=True)
            elif args.command == "failed":
                collection.failed(args.id, args.error)
            elif args.command == "scan":
                for fileId, item in collection.items.items():
                    fileName = item["fileName"]
                    if Path(fileName).name != fileName or "/" in fileName or "\\" in fileName:
                        collection.failed(fileId, "Inventory filename must be a basename")
                        continue
                    collection.completed(fileId, args.downloads / fileName, args.retry_failed, args.recheck)
                collection.check()
            elif args.command == "watch":
                if args.idle_seconds <= 0 or args.poll_seconds <= 0:
                    raise ValueError("Watcher intervals must be positive")
                watchDownloads(collection, args.downloads.resolve(), args.finish_file,
                               args.idle_seconds, args.poll_seconds)
            elif args.command == "flush":
                collection.check()
            if args.command not in {"completed", "failed"}:
                collection.export()
            entries = [entry for fileId, entry in collection.entries.items()
                       if entry.get("item") == collection.items.get(fileId)]
            counts = {status: sum(entry["status"] == status for entry in entries)
                      for status in ("verified", "failed", "pending")}
            counts["unrecorded"] = len(collection.items) - len(entries)
            print(json.dumps(counts))
            return 1 if counts["failed"] else 0
    except (OSError, ValueError, KeyError, TypeError) as error:
        print(str(error), file=sys.stderr)
        return 2


if __name__ == "__main__":
    sys.exit(main())
