"""What a file actually is, from its first bytes, so a reader never trusts a filename or a declared kind."""

import zipfile
from pathlib import Path


def fileType(path: Path) -> str | None:
    """"pdf", "xlsx" (an Office Open XML workbook), "zip" (any other archive), or None when unrecognized."""
    with path.open("rb") as handle:
        head = handle.read(8)
    if head.startswith(b"%PDF-"):
        return "pdf"
    if head.startswith(b"PK\x03\x04"):
        try:
            with zipfile.ZipFile(path) as archive:
                return "xlsx" if "xl/workbook.xml" in archive.namelist() else "zip"
        except zipfile.BadZipFile:
            return None
    return None
