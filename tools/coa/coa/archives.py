"""Copies the two statement workbooks out of COA Annual Audit Report ZIPs into data/towns/financial-statements/."""

import csv
import hashlib
import re
import unicodedata
import zipfile
from pathlib import Path

MEMBERS = {
    "fs_workbook": (re.compile(r"(?:^|/)07-[^/]*FS\.xlsx$", re.I), "FS"),
    "annex_workbook": (re.compile(r"(?:^|/)11-[^/]*Annexes\.xlsx$", re.I), "Annexes"),
}
SOURCE_COLUMNS = [
    "year", "psgc_code", "town", "kind", "file", "bytes", "sha256",
    "archive_member", "archive_sha256", "source_url", "retrieved_on",
]


def key(name: str) -> str:
    """Folder and file names spell towns differently ("Sto-Nino", "Sto.-Nino", "StoNiño")."""
    asciiName = unicodedata.normalize("NFKD", name).encode("ascii", "ignore").decode()
    return re.sub(r"[^a-z]", "", asciiName.lower())


def addWorkbooks(listing: Path, summaries: Path, target: Path) -> list[str]:
    """Each listing row names a downloaded ZIP, its exact COA URL and the download date."""
    towns = {}
    with (summaries / "sources.csv").open(encoding="utf-8", newline="") as handle:
        for row in csv.DictReader(handle):
            folder = Path(row["file"]).parts[1]
            towns[key(folder)] = (row["psgc_code"], row["town"], folder)

    sourcesPath = target / "sources.csv"
    existing = {}
    if sourcesPath.exists():
        with sourcesPath.open(encoding="utf-8", newline="") as handle:
            existing = {(r["year"], r["psgc_code"], r["kind"]): r for r in csv.DictReader(handle)}

    messages = []
    with listing.open(encoding="utf-8", newline="") as handle:
        for item in csv.DictReader(handle):
            archive = Path(item["archive"])
            found = re.match(r"(.+?)-Annual-Audit-Report-(20\d\d)", archive.name, re.I)
            if not found or key(found.group(1)) not in towns:
                raise ValueError(f"{archive.name}: cannot tell the town and year from the file name")
            year = found.group(2)
            psgc, town, folder = towns[key(found.group(1))]
            archiveSha = hashlib.sha256(archive.read_bytes()).hexdigest()
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
                    existing[(year, psgc, kind)] = {
                        "year": year, "psgc_code": psgc, "town": town, "kind": kind,
                        "file": relative.as_posix(), "bytes": len(data), "sha256": hashlib.sha256(data).hexdigest(),
                        "archive_member": members[0], "archive_sha256": archiveSha,
                        "source_url": item["source_url"], "retrieved_on": item["retrieved_on"],
                    }
                    messages.append(f"{year} {town} {suffix}: {relative.as_posix()}")

    with sourcesPath.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=SOURCE_COLUMNS, lineterminator="\n")
        writer.writeheader()
        writer.writerows(sorted(existing.values(), key=lambda r: (r["year"], r["town"], r["kind"])))
    return messages
