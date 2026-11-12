from decimal import Decimal
from pathlib import Path

import pytest
import openpyxl

from coa.lines import load
from coa.workbook import amount, readAnnex, readFs

ROOT = Path(__file__).resolve().parents[3]
WORKBOOKS = ROOT / "data" / "towns" / "financial-statements"
LINES, ALIASES = load(ROOT / "data" / "towns" / "finances" / "lines.csv")


def read(year: int, folder: str, kind: str):
    reader = readFs if kind == "FS" else readAnnex
    return reader(WORKBOOKS / str(year) / folder / f"{folder}-{kind}-{year}.xlsx", year, LINES, ALIASES)


def values(result) -> dict[tuple[str, int, str], Decimal]:
    return {(figure.code, figure.fiscalYear, figure.basis): figure.value for figure in result.figures}


def saveWorkbook(tmpPath, sheets):
    book = openpyxl.Workbook()
    book.remove(book.active)
    for name, rows in sheets:
        sheet = book.create_sheet(name)
        for row in rows:
            sheet.append(row)
    path = tmpPath / "source.xlsx"
    book.save(path)
    book.close()
    return path


def performanceRows(current=100):
    return [("Consolidated Statement of Financial Performance",), (None, 2025, 2024),
            ("Tax Revenue", current, 90), ("Personnel Services", 50, 40), ("Total Revenue", current, 90)]


@pytest.mark.parametrize("reverse", [False, True])
def testEquallyScoringSheetsAreWithheldRegardlessOfWorkbookOrder(tmp_path, reverse):
    sheets = [("First", performanceRows(100)), ("Second", performanceRows(200))]
    path = saveWorkbook(tmp_path, list(reversed(sheets)) if reverse else sheets)

    result = readFs(path, 2025, LINES, ALIASES)

    assert result.figures == []
    issue = next(issue for issue in result.issues if issue.kind == "ambiguous_sheets")
    assert issue.severity == "error"
    assert "First, Second" in issue.message


@pytest.mark.parametrize("reader,label,title", [
    (readFs, "Tax Revenue", "Consolidated Statement of Financial Performance"),
    (readAnnex, "Business Tax", "Detailed Statement of Financial Performance General Fund"),
])
@pytest.mark.parametrize("reverse", [False, True])
def testTiedNumericColumnCandidatesAreNotChosenByPosition(tmp_path, reader, label, title, reverse):
    first, second = (200, 100) if reverse else (100, 200)
    rows = [(title,), (None, 2025, None, 2024), (label, first, second, 90),
            ("Personnel Services", 50, 60, 40), ("Total Revenue", first, second, 90)]
    path = saveWorkbook(tmp_path, [("Performance", rows)])

    result = reader(path, 2025, LINES, ALIASES)

    assert result.figures == []
    issue = next(issue for issue in result.issues if issue.kind == "year_columns_ambiguous")
    assert issue.severity == "error"
    assert "columns B, C" in issue.message


def testRepeatedFiscalYearHeadersAreAnExplicitAmbiguity(tmp_path):
    rows = performanceRows()
    rows[1] = (None, 2025, 2025)
    path = saveWorkbook(tmp_path, [("Performance", rows)])

    result = readFs(path, 2025, LINES, ALIASES)

    assert result.figures == []
    assert any(issue.kind == "year_columns_ambiguous" and "repeated headers" in issue.message for issue in result.issues)


@pytest.mark.parametrize("reverse", [False, True])
def testConflictingDuplicateRowsWithholdTheLineInsteadOfKeepingTheFirst(tmp_path, reverse):
    rows = performanceRows()
    duplicates = [("Tax Revenue", 100, 90), ("Tax Revenue", 200, 90)]
    rows[2:3] = list(reversed(duplicates)) if reverse else duplicates
    path = saveWorkbook(tmp_path, [("Performance", rows)])

    result = readFs(path, 2025, LINES, ALIASES)

    assert not any(figure.code == "revenue.tax" for figure in result.figures)
    issue = next(issue for issue in result.issues if issue.kind == "conflicting_values")
    assert issue.code == "revenue.tax"
    assert issue.severity == "error"
    assert "Performance!B3" in issue.message and "Performance!B4" in issue.message


def testIdenticalDuplicateRowsAreNotCountedTwice(tmp_path):
    rows = performanceRows()
    rows.insert(3, rows[2])
    path = saveWorkbook(tmp_path, [("Performance", rows)])

    result = readFs(path, 2025, LINES, ALIASES)

    figures = [figure for figure in result.figures if figure.code == "revenue.tax"]
    assert sorted((figure.fiscalYear, figure.value) for figure in figures) == [(2024, Decimal("90.00")), (2025, Decimal("100.00"))]
    assert not any(issue.kind == "conflicting_values" for issue in result.issues)


def testMissingComparativeComponentDoesNotBecomeZeroInASummedLine(tmp_path):
    rows = performanceRows()
    rows[4] = ("Total Revenue", 170, 120)
    rows += [("Gains", 20, 10), ("Miscellaneous Income", 50, None)]
    path = saveWorkbook(tmp_path, [("Performance", rows)])

    result = readFs(path, 2025, LINES, ALIASES)

    figures = [figure for figure in result.figures if figure.code == "revenue.other"]
    assert [(figure.fiscalYear, figure.value) for figure in figures] == [(2025, Decimal("70.00"))]
    assert [item["location"] for item in figures[0].inputs] == ["Performance!B6", "Performance!B7"]
    assert any(issue.kind == "incomplete_components" and issue.code == "revenue.other" and "Performance!C7" in issue.message for issue in result.issues)


@pytest.mark.parametrize("reverse", [False, True])
@pytest.mark.parametrize("missing", [None, "unreadable"])
def testWhollyUnreadableComponentCannotDisappearFromASummedLine(tmp_path, reverse, missing):
    components = [("Gains", 20, 10), ("Miscellaneous Income", missing, missing)]
    rows = performanceRows() + (list(reversed(components)) if reverse else components)
    path = saveWorkbook(tmp_path, [("Performance", rows)])

    result = readFs(path, 2025, LINES, ALIASES)

    assert not any(figure.code == "revenue.other" for figure in result.figures)
    issue = next(issue for issue in result.issues if issue.kind == "incomplete_components")
    missingRow = 6 if reverse else 7
    assert issue.severity == "error" and issue.code == "revenue.other"
    assert f"Performance!B{missingRow}" in issue.message and f"Performance!C{missingRow}" in issue.message


def testAllSummedComponentsUnreadableAreReportedWithoutInventingAnAmount(tmp_path):
    rows = performanceRows() + [("Gains", None, None), ("Miscellaneous Income", None, None)]
    path = saveWorkbook(tmp_path, [("Performance", rows)])

    result = readFs(path, 2025, LINES, ALIASES)

    assert not any(figure.code == "revenue.other" for figure in result.figures)
    assert len([issue for issue in result.issues if issue.kind == "incomplete_components"]) == 2


@pytest.mark.parametrize("cell", [float("nan"), float("inf"), float("-inf")])
def testNonFiniteNumericCellsAreNotFinancialAmounts(cell):
    assert amount(cell) is None


def contextualRows(kind, identity="Municipality of Abulug, Cagayan"):
    rows = performanceRows()
    if kind == "annex":
        rows[0] = ("Detailed Statement of Financial Performance General Fund",)
        rows[2] = ("Business Tax", 100, 90)
    rows.insert(1, (identity,))
    return rows


@pytest.mark.parametrize("reader,kind,code", [(readFs, "fs", "revenue.tax"), (readAnnex, "annex", "local.business_tax")])
def testSelectedSheetIdentityComesFromCellsRatherThanFilename(tmp_path, reader, kind, code):
    rows = contextualRows(kind)
    rows.insert(2, ("Amounts in Philippine pesos",))
    path = saveWorkbook(tmp_path, [("Performance", rows)])
    renamed = path.rename(tmp_path / "Alcala.xlsx")

    result = reader(renamed, 2025, LINES, ALIASES, expectedTown="Abulug", expectedProvince="Cagayan")

    assert values(result)[(code, 2025, "actual")] == Decimal("100.00")
    assert not any(issue.kind in ("municipality_mismatch", "municipality_unconfirmed", "province_unconfirmed", "unsupported_units") for issue in result.issues)


@pytest.mark.parametrize("reader,kind", [(readFs, "fs"), (readAnnex, "annex")])
@pytest.mark.parametrize("identity,expectedKind", [
    ("Municipality of Alcala, Cagayan", "municipality_mismatch"),
    ("Municipality of Abulug, Aurora", "province_unconfirmed"),
    ("Government Financial Statements", "municipality_unconfirmed"),
])
def testSheetIdentityDistinguishesConflictFromUnconfirmedContext(tmp_path, reader, kind, identity, expectedKind):
    path = saveWorkbook(tmp_path, [("Performance", contextualRows(kind, identity))])
    renamed = path.rename(tmp_path / "Abulug.xlsx")

    result = reader(renamed, 2025, LINES, ALIASES, expectedTown="Abulug", expectedProvince="Cagayan")

    issue = next(issue for issue in result.issues if issue.kind == expectedKind)
    if expectedKind.endswith('_mismatch'):
        assert result.figures == []
        assert issue.severity == 'error'
    else:
        assert result.figures
        assert issue.severity == 'warning'
    assert issue.page is None
    assert "Abulug.xlsx!Performance" in issue.message and "A2" in issue.message
    assert "First-page" not in issue.message


@pytest.mark.parametrize("reader,kind", [(readFs, "fs"), (readAnnex, "annex")])
@pytest.mark.parametrize("unit", ["Amounts in thousands of pesos", "Amounts are expressed in millions", "Amounts in USD", "Amounts in PHP'000"])
def testUnsupportedSheetUnitsAreNotAssumedToBePesosOrConverted(tmp_path, reader, kind, unit):
    rows = contextualRows(kind)
    rows.insert(2, (unit,))
    path = saveWorkbook(tmp_path, [("Performance", rows)])

    result = reader(path, 2025, LINES, ALIASES, expectedTown="Abulug", expectedProvince="Cagayan")

    assert result.figures == []
    issue = next(issue for issue in result.issues if issue.kind == "unsupported_units")
    assert issue.severity == "error"
    assert issue.page is None
    assert "Performance!A3" in issue.message and unit in issue.message


# Expected values were read from the workbook cells by hand; sector totals add the class rows shown in the comments.
CASES = {
    (2024, "Tuguegarao-City", "FS"): {
        ("revenue.tax", 2024, "actual"): "395914007.42",
        ("revenue.tax", 2023, "actual"): "317584275.03",
        ("revenue.national_share", 2024, "actual"): "898158650.00",
        ("revenue.service_business", 2024, "actual"): "169615661.43",
        ("expense.personnel", 2024, "actual"): "467931505.53",
        ("expense.non_cash", 2024, "actual"): "158863071.50",
        ("expense.transfers", 2024, "actual"): "24726697.45",
        # 280489664.81 + 393076880.97 + 28672293.88 + 105801733.71
        ("sector.general_public_services", 2024, "actual"): "808040573.37",
        # 301527887 + 443034565 + 132329588.99 + 107007607
        ("sector.general_public_services", 2024, "budget_final"): "983899647.99",
    },
    (2025, "Gattaran", "FS"): {
        # 3986087.58 + 1562207.20 + 633187.00
        ("sector.economic_services", 2025, "actual"): "6181481.78",
        ("sector.economic_services", 2025, "budget_final"): "8170000.00",
    },
    (2025, "Baggao", "FS"): {
        ("revenue.tax", 2025, "actual"): "18079388.97",
        ("revenue.national_share", 2025, "actual"): "569103533.00",
        ("expense.transfers", 2025, "actual"): "17637722.54",
    },
    (2025, "Amulung", "FS"): {("revenue.grants", 2025, "actual"): "39820764.69"},
    (2025, "Piat", "FS"): {("revenue.national_share", 2025, "actual"): "167649921.00"},
    (2025, "Gattaran", "Annexes"): {
        ("local.business_tax", 2025, "actual"): "13786235.92",
        ("local.business_tax", 2024, "actual"): "12898862.85",
        # 1769582.09 basic less the 106758.03 discount
        ("local.real_property_tax", 2025, "actual"): "1662824.06",
    },
    (2024, "Alcala", "Annexes"): {
        ("local.business_tax", 2024, "actual"): "1821525.55",
        ("local.business_tax", 2023, "actual"): "1965249.95",
    },
}


@pytest.mark.parametrize(("workbook", "expected"), CASES.items(), ids=["-".join(map(str, key)) for key in CASES])
def testReadsEachWorkbookLayout(workbook, expected):
    found = values(read(*workbook))

    assert {key: found.get(key) for key in expected} == {key: Decimal(value) for key, value in expected.items()}


def testFlagsLocalTaxesThatMayIncludeTheNationalShare():
    result = read(2025, "Enrile", "FS")

    assert "national_share_not_separate" in {issue.kind for issue in result.issues}
    assert {figure.variant for figure in result.figures if figure.code == "revenue.tax"} == {"may_include_national_share"}


def testFlagsRevenueSourcesThatDoNotAddUp():
    assert "revenue_sources_mismatch" in {issue.kind for issue in read(2025, "Sto.-Nino", "FS").issues}


def testFlagsABudgetSheetTitledWithTheWrongYear():
    assert "sheet_year_mismatch" in {issue.kind for issue in read(2025, "Gattaran", "FS").issues}


def testReportsAMissingGeneralFundDetailInsteadOfGuessing():
    result = read(2024, "Abulug", "Annexes")

    assert result.figures == []
    assert {issue.kind for issue in result.issues} == {"sheet_not_found"}

@pytest.mark.parametrize(("code", "basis", "column", "rows", "expected"), [
    ("sector.economic_services", "budget_final", "G", [61, 62, 63, 64], "161160894.00"),
    ("sector.economic_services", "actual", "K", [61, 62, 63, 64], "99846291.10"),
    ("sector.development_fund", "budget_final", "G", [69, 70], "224537355.00"),
    ("sector.development_fund", "actual", "K", [69, 70], "54562727.33"),
])
def testTuguegaraoBudgetSumsCiteEveryContributingAmount(code, basis, column, rows, expected):
    result = read(2025, "Tuguegarao-City", "FS")
    figure = next(item for item in result.figures if (item.code, item.fiscalYear, item.basis) == (code, 2025, basis))

    assert figure.value == Decimal(expected)
    assert [item["location"] for item in figure.inputs] == [f"11 - SCBAA!{column}{row}" for row in rows]
    assert figure.location == "; ".join(item["location"] for item in figure.inputs)
    assert sum(Decimal(item["value"]) for item in figure.inputs) == Decimal(expected)


@pytest.mark.parametrize(("year", "cells", "amounts", "expected"), [
    (2024, ["F19", "F20"], ["56878423.47", "-8229120.59"], "48649302.88"),
    (2025, ["G18", "G19"], ["60927153.30", "-9648587.97"], "51278565.33"),
])
def testBasicPropertyTaxCitesTheSignedDiscount(year, cells, amounts, expected):
    result = read(year, "Tuguegarao-City", "Annexes")
    figure = next(item for item in result.figures if (item.code, item.fiscalYear) == ("local.real_property_tax", year))

    assert figure.value == Decimal(expected)
    assert list(figure.inputs) == [{"location": f"ANNEX-B!{cell}", "value": value} for cell, value in zip(cells, amounts)]


def testCheckOnlyTotalRevenueIsReadFromTheWorkbookButNotEligibleForIntake():
    from coa.intake import isDisplayed

    result = read(2025, "Tuguegarao-City", "FS")
    totals = {figure.fiscalYear: figure for figure in result.figures if figure.code == "fs.total_revenue"}

    assert {year: (figure.value, figure.location) for year, figure in totals.items()} == {
        2025: (Decimal("1679019473.60"), "8 - Financial Performance 2025!D14"),
        2024: (Decimal("1465722146.22"), "8 - Financial Performance 2025!F14"),
    }
    assert not isDisplayed("fs.total_revenue", LINES)
