"""Copies the two statement workbooks out of COA Annual Audit Report ZIPs into data/towns/financial-statements/."""

import csv
import hashlib
import re
import unicodedata
import zipfile
from pathlib import Path

MEMBERS = {
    "fs_workbook": (re.compile(r"(?:^|[/\\])07[-_][^/\\]*(?:FS|Financial[_ -]Statements)[ _-]*(?:NEW|\d+)?\.xlsx$", re.I), "FS"),
    "annex_workbook": (re.compile(r"(?:^|[/\\])11[-_][^/\\]*(?:Annexes|Appendices)[ _-]*(?:NEW|\d+)?\.xlsx$", re.I), "Annexes"),
}
INDEX_COLUMNS = [
    "report_year", "municipality", "kind", "file", "sha256", "source_url",
    "original_archive_members", "original_collection_files",
]


def key(name: str) -> str:
    """Folder and file names spell towns differently ("Sto-Nino", "Sto.-Nino", "StoNiño")."""
    asciiName = unicodedata.normalize("NFKD", name).encode("ascii", "ignore").decode()
    return re.sub(r"[^a-z]", "", asciiName.lower())


def addWorkbooks(listing: Path, summaries: Path, target: Path) -> list[str]:
    """Each listing row names a downloaded ZIP and its exact COA URL; the workbooks are registered in index.csv."""
    towns = {}
    with (summaries / "sources.csv").open(encoding="utf-8", newline="") as handle:
        for row in csv.DictReader(handle):
            folder = Path(row["file"]).parts[1]
            towns[key(folder)] = (row["psgc_code"], row["town"], folder)

    # The single workbook register (index.csv), written back with its byte-order mark and CRLF line endings.
    indexPath = target / "index.csv"
    existing = {}
    if indexPath.exists():
        with indexPath.open(encoding="utf-8-sig", newline="") as handle:
            existing = {(r["report_year"], r["municipality"], r["kind"]): r for r in csv.DictReader(handle)}

    messages = []
    with listing.open(encoding="utf-8", newline="") as handle:
        for item in csv.DictReader(handle):
            archive = Path(item["archive"])
            found = re.match(r"(.+?)-Annual-Audit-Report-(20\d\d)", archive.name, re.I)
            if not found or key(found.group(1)) not in towns:
                raise ValueError(f"{archive.name}: cannot tell the town and year from the file name")
            year = found.group(2)
            _, town, folder = towns[key(found.group(1))]
            with zipfile.ZipFile(archive) as bundle:
                for kind, (pattern, suffix) in MEMBERS.items():
                    members = [name for name in bundle.namelist() if pattern.search(name)]
                    if len(members) != 1:
                        messages.append(f"{archive.name}: expected one {suffix} workbook, found {len(members)}")
                        continue
                    data = bundle.read(members[0])
                    relative = Path(year) / folder / f"{folder}-{suffix}-{year}.xlsx"
                    (target / relative).parent.mkdir(parents=True, exist_ok=True)
                    (target / relative).write_bytes(data)
                    existing[(year, town, kind)] = {
                        "report_year": year, "municipality": town, "kind": kind,
                        "file": f"financial-statements/{relative.as_posix()}", "sha256": hashlib.sha256(data).hexdigest(),
                        "source_url": item["source_url"], "original_archive_members": members[0],
                        "original_collection_files": "",
                    }
                    messages.append(f"{year} {town} {suffix}: {relative.as_posix()}")

    with indexPath.open("w", encoding="utf-8-sig", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=INDEX_COLUMNS, lineterminator="\r\n")
        writer.writeheader()
        writer.writerows(sorted(existing.values(), key=lambda r: (r["report_year"], r["municipality"], r["kind"])))
    return messages
