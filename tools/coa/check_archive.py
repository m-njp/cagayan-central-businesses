"""Check a downloaded public report ZIP against the approved report checksums."""

import argparse
import csv
import hashlib
import json
import sys
from pathlib import Path, PurePosixPath
from zipfile import BadZipFile, ZipFile

from coa.progress import report


def checkArchive(path: Path, events: bool) -> int:
    progress = (lambda event: print(json.dumps(event), flush=True)) if events else None
    ledger = Path(__file__).resolve().parents[2] / "data/towns/finances/documents.csv"
    with ledger.open(encoding="utf-8-sig", newline="") as source:
        approved = {(Path(row["file"]).name, row["sha256"]) for row in csv.DictReader(source) if row["file"]}

    report(progress, "stage", stage="inspecting", message="Checking archive contents.")
    with ZipFile(path) as archive:
        names = archive.namelist()
        manifest = json.loads(archive.read("manifest.json"))
        if not isinstance(manifest, dict) or not manifest or sorted(names) != sorted(["manifest.json", *manifest]):
            raise ValueError("Archive entries do not match the manifest")

        report(progress, "stage", stage="scanning", message="Verifying report checksums.")
        for index, (name, expected) in enumerate(manifest.items(), start=1):
            entry = PurePosixPath(name)
            if entry.is_absolute() or len(entry.parts) != 3 or ".." in entry.parts or "\\" in name:
                raise ValueError(f"Invalid archive path: {name}")
            if not isinstance(expected, str) or (entry.name, expected) not in approved:
                raise ValueError(f"Report is not in the approved ledger: {name}")
            with archive.open(name) as contents:
                if hashlib.file_digest(contents, "sha256").hexdigest() != expected:
                    raise ValueError(f"Checksum mismatch: {name}")
            report(progress, "progress", current=index, total=len(manifest), message=name)

    report(progress, "done", message=f"Verified {len(manifest)} reports.")
    return len(manifest)


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("archive", type=Path)
    parser.add_argument("--events", action="store_true", help="Emit JSON progress lines.")
    args = parser.parse_args()
    try:
        count = checkArchive(args.archive, args.events)
    except (BadZipFile, KeyError, OSError, RuntimeError, ValueError) as error:
        print(f"Archive verification failed: {error}", file=sys.stderr)
        return 1
    if not args.events:
        print(f"Verified {count} reports.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
