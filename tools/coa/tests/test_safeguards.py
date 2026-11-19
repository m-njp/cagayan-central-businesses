"""Safeguards that keep one government's figures from being shown as another's, or a misread file from being read."""

from pathlib import Path

import openpyxl

from coa.checks import crossGovernmentDuplicates, implausibleChanges
from coa.files import fileType
from coa.lines import load
from coa.summary import parseSummary

ROOT = Path(__file__).resolve().parents[3]
LINES, ALIASES = load(ROOT / "data" / "towns" / "finances" / "lines.csv")


def row(town, code, year, value, reported=None, kind="annex_workbook"):
    return {"psgc_code": town[:3], "town": town, "reported_in_year": str(reported or year), "document_kind": kind,
            "fiscal_year": str(year), "line_code": code, "value": value, "location": "B1!F14", "document_sha256": "0" * 64}


def testTheSameAmountUnderTwoGovernmentsHoldsBothForAReviewer():
    rows = [row("Enrile", "local.permit_fees", 2018, "2540951.56", reported=2019), row("Solana", "local.permit_fees", 2018, "2540951.56"),
            row("Enrile", "local.permit_fees", 2017, "661015.00", reported=2018), row("Enrile", "local.permit_fees", 2017, "661015.00")]

    issues = crossGovernmentDuplicates(rows)

    # The same government repeating its own amount in a later report is the normal prior-year column, not a duplicate.
    assert sorted((issue["town"], issue["kind"], issue["severity"]) for issue in issues) == [
        ("Enrile", "duplicate_across_governments", "error"), ("Solana", "duplicate_across_governments", "error")]
    assert "also recorded for Solana" in next(issue["message"] for issue in issues if issue["town"] == "Enrile")


def testAZeroAmountIsNeverADuplicate():
    assert crossGovernmentDuplicates([row("Enrile", "local.market_operations", 2018, "0.00"), row("Solana", "local.market_operations", 2018, "0.00")]) == []


def testAChangeOfMoreThanThreeTimesIsHeldButSmallLinesAndOrdinaryChangesAreNot():
    rows = [
        row("Aparri", "revenue.tax", 2024, "40000000.00"), row("Aparri", "revenue.tax", 2023, "10000000.00", reported=2024),
        row("Iguig", "revenue.tax", 2024, "12000000.00"), row("Iguig", "revenue.tax", 2023, "10000000.00", reported=2024),
        row("Piat", "local.market_operations", 2024, "90000.00"), row("Piat", "local.market_operations", 2023, "10000.00", reported=2024),
    ]

    issues = implausibleChanges(rows)

    assert [(issue["town"], issue["kind"], issue["severity"]) for issue in issues] == [("Aparri", "implausible_change", "warning")]


def testTheYearBeforeComesFromThePreviousReportWhenThisReportHasNoPriorColumn():
    rows = [row("Aparri", "revenue.tax", 2024, "1500000.00"), row("Aparri", "revenue.tax", 2023, "9000000.00")]

    assert [issue["town"] for issue in implausibleChanges(rows)] == ["Aparri"]


def testEveryDifferenceIsFlaggedAndOnlyThoseOverOnePesoHoldSignOff():
    from decimal import Decimal

    from coa.checks import documentChecks
    from coa.summary import Figure

    def position(assets, liabilities, equity):
        return [Figure("position.assets", 2025, Decimal(assets), None, "Assets", 1),
                Figure("position.liabilities", 2025, Decimal(liabilities), None, "Liabilities", 1),
                Figure("position.equity", 2025, Decimal(equity), None, "Government Equity", 1)]

    balanced = documentChecks(position("100.00", "60.00", "40.00"), {}, 2025)
    centavos = documentChecks(position("100.32", "60.00", "40.00"), {}, 2025)
    pesos = documentChecks(position("105.00", "60.00", "40.00"), {}, 2025)

    assert balanced == []
    assert [(issue.kind, issue.severity) for issue in centavos] == [("minor_difference", "warning")]
    assert "₱0.32" in centavos[0].message and "₱100.32" in centavos[0].message
    assert [(issue.kind, issue.severity) for issue in pesos] == [("balance_mismatch", "error")]


def testAFileIsIdentifiedByItsContentNotItsName(tmp_path):
    workbook = tmp_path / "report.pdf"
    book = openpyxl.Workbook()
    book.save(workbook)
    pdf = tmp_path / "statements.xlsx"
    pdf.write_bytes(b"%PDF-1.7\n%\xe2\xe3\xcf\xd3\n")

    assert fileType(workbook) == "xlsx"
    assert fileType(pdf) == "pdf"
    # A workbook named .pdf is refused by the summary reader.
    summary = parseSummary(workbook, 2024, LINES, ALIASES)
    assert summary.figures == []
    assert all(issue.kind == "engine_failed" and "not a PDF" in issue.message for issue in summary.issues if issue.kind == "engine_failed")
    assert sum(issue.kind == "engine_failed" for issue in summary.issues) == 2
