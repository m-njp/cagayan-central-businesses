"""Only an Executive Summary PDF is read; workbooks and archives stay in the source library (D49)."""

import zipfile
from pathlib import Path

import pytest

from coa.intake import scan

ROOT = Path(__file__).resolve().parents[3]
FS = ROOT / "data/towns/financial-statements/2025/Tuguegarao-City/Tuguegarao-City-FS-2025.xlsx"


def testAWorkbookIsRefusedAndLeftUntouched():
    before = FS.read_bytes()

    with pytest.raises(ValueError, match="Only an Executive Summary PDF"):
        scan(FS, 2025)

    assert FS.read_bytes() == before


def testAnArchiveOrAWorkbookNamedPdfIsRefused(tmp_path):
    archive = tmp_path / "reports.zip"
    with zipfile.ZipFile(archive, "w") as bundle:
        bundle.write(FS, "07-Tuguegarao-FS.xlsx")
    disguised = tmp_path / "summary.pdf"
    disguised.write_bytes(FS.read_bytes())

    for path in (archive, disguised):
        with pytest.raises(ValueError, match="Only an Executive Summary PDF"):
            scan(path, 2025)
