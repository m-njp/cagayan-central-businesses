"""Only the uploaded report and recognized archive members enter an intake result."""

import hashlib
import zipfile
from pathlib import Path

import openpyxl
import pytest

from coa.intake import scan

ROOT = Path(__file__).resolve().parents[3]
FS = ROOT / "data/towns/financial-statements/2025/Tuguegarao-City/Tuguegarao-City-FS-2025.xlsx"


def testArchiveUsesFixedChildNamesAndIgnoresUnrecognizedMembers(tmp_path):
    archive = tmp_path / "reports.zip"
    output = tmp_path / "parsed"
    output.mkdir()
    with zipfile.ZipFile(archive, "w") as bundle:
        bundle.write(FS, "../../07-Tuguegarao-FS.xlsx")
        bundle.writestr("../../unrelated.txt", "not a source")

    result = scan(archive, "archive", 2025, output, 10, 100_000_000)

    assert result["children"] == [{
        "kind": "fs_workbook", "name": "fs_workbook.xlsx", "member": "../../07-Tuguegarao-FS.xlsx",
        "sha256": hashlib.sha256(FS.read_bytes()).hexdigest(),
    }]
    assert sorted(path.name for path in output.iterdir()) == ["fs_workbook.xlsx"]
    assert not (tmp_path.parent / "unrelated.txt").exists()
    taxRevenue = next((row for row in result["figures"] if row["line_code"] == "revenue.tax" and row["fiscal_year"] == 2025), None)
    assert taxRevenue is not None
    assert taxRevenue["value"] == "430895820.24"
    assert [item["location"] for item in taxRevenue["inputs"]] == ["8 - Financial Performance 2025!D9"]
    # Lines withdrawn from display, such as Budget and Actual Amounts (metrics.md M23), never enter an upload.
    assert not any(row["line_code"] == "sector.economic_services" for row in result["figures"])


@pytest.mark.parametrize(("members", "expanded"), [(1, 100_000_000), (10, 1)])
def testRejectsArchivesBeyondConfiguredLimits(tmp_path, members, expanded):
    archive = tmp_path / "reports.zip"
    with zipfile.ZipFile(archive, "w") as bundle:
        bundle.writestr("one.txt", "one")
        bundle.writestr("two.txt", "two")

    with pytest.raises(ValueError, match="configured extraction limits"):
        scan(archive, "archive", 2025, tmp_path, members, expanded)


def testRejectsAmbiguousWorkbookMembers(tmp_path):
    archive = tmp_path / "reports.zip"
    with zipfile.ZipFile(archive, "w") as bundle:
        bundle.writestr("a/07-First-FS.xlsx", "first")
        bundle.writestr("b/07-Second-FS.xlsx", "second")

    with pytest.raises(ValueError, match="ambiguous"):
        scan(archive, "archive", 2025, tmp_path, 10, 100_000_000)


def testUnsupportedWorkbookKeepsAnEmptyResultForManualEntry(tmp_path):
    source = tmp_path / "unknown.xlsx"
    book = openpyxl.Workbook()
    book.active["A1"] = "An unsupported report layout"
    book.save(source)
    book.close()

    result = scan(source, "fs_workbook", 2025, tmp_path, 10, 100_000_000)

    assert result["figures"] == []
    assert result["issues"]
    assert source.exists()
