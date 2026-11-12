from decimal import Decimal
from pathlib import Path

import pytest

from coa.checks import documentChecks, mislabelledYears, restatementChecks, swapYears
from coa.lines import load
from coa.summary import parseSummary, parseText

ROOT = Path(__file__).resolve().parents[3]
SUMMARIES = ROOT / "data" / "towns" / "executive-summaries"
LINES, ALIASES = load(ROOT / "data" / "towns" / "finances" / "lines.csv")


def parse(year: int, folder: str):
    path = next((SUMMARIES / str(year) / folder).glob("*.pdf"))
    return parseSummary(path, year, LINES, ALIASES)


def values(result) -> dict[tuple[str, int], Decimal]:
    return {(figure.code, figure.fiscalYear): figure.value for figure in result.figures}


@pytest.mark.parametrize(("row", "expected", "warning"), [
    ("Total Income 161,123,318.30 150,883,392.66 10,239,925.64 7", "161123318.30", False),
    ("Total Income 161,123,318.30 150,883,392.66 10,239,925.64 (7.00%)", "161123318.30", False),
    ("Total Income P=115,719,714.68 P=144,610,946.50 (28,891,231.82)", "115719714.68", False),
    ("Income 217,772,319.49 199,052,338.47 18719981.02 9.00%", "217772319.49", False),
    ("Income 186,488,805.85 165,029,450.20 21,468355.65 13%", "186488805.85", True),
])
def testOlderNumericLayoutsRetainReadableAmountsAndFlagMalformedTrailingColumns(row, expected, warning):
    result = parseText([(1, "Financial Highlights"), (1, "2017 2016"), (1, row)], 2017, LINES, ALIASES)
    assert values(result)[("performance.revenue", 2017)] == Decimal(expected)
    assert ("unreadable_change" in {issue.kind for issue in result.issues}) == warning


def testMalformedMainAmountIsNotGuessed():
    result = parseText([(1, "Financial Highlights"), (1, "2016 2015"),
                        (1, "Total Income P 48,014,545,75 P 43,668,723.14 P 4,345,822.61")], 2016, LINES, ALIASES)
    assert not any(figure.code == "performance.revenue" for figure in result.figures)


def testLongWrappedExpenseQualifierKeepsItsPageAndDefinition():
    result = parse(2014, "Gattaran")
    current = next(figure for figure in result.figures if figure.code == "performance.expenses" and figure.fiscalYear == 2014)
    assert current.value == Decimal("124219472.51")
    assert current.page == 2
    assert current.variant == "includes_subsidy_to_other_funds"


# Expected values were read from each PDF's highlights table by hand, not from parser output.
LAYOUTS = {
    (2024, "Tuguegarao-City"): {
        ("position.assets", 2024): "4909278026.68", ("position.assets", 2023): "4755696722.35",
        ("position.liabilities", 2024): "1323381986.04", ("position.equity", 2024): "3585896040.64",
        ("performance.revenue", 2024): "1465722146.22", ("performance.revenue", 2023): "1328465373.08",
        ("performance.expenses", 2024): "1184642113.21", ("performance.net_income", 2023): "166669261.71",
        ("budget.appropriations_current", 2024): "1568978181.74", ("budget.obligations_current", 2023): "1170902599.47",
    },
    (2020, "Abulug"): {
        ("position.assets", 2020): "445737992.91", ("position.assets", 2019): "396488332.90",
        ("position.equity", 2019): "291128530.01", ("performance.revenue", 2020): "145949953.21",
        ("performance.expenses", 2019): "114319571.04", ("budget.appropriations_current", 2020): "153094380.00",
        ("budget.obligations_current", 2019): "117990546.25",
    },
    (2020, "Sto.-Nino"): {
        ("position.equity", 2020): "349107340.82", ("performance.revenue", 2020): "189806177.11",
        ("performance.revenue", 2019): "169257233.93", ("performance.expenses", 2020): "127319290.69",
        ("budget.appropriations_current", 2019): "169304878.99", ("budget.obligations_current", 2020): "149630370.66",
    },
    (2021, "Baggao"): {
        ("position.assets", 2021): "982072784.49", ("position.equity", 2020): "693704494.90",
        ("performance.revenue", 2021): "417659631.01", ("performance.expenses", 2020): "317013611.97",
    },
    (2023, "Rizal"): {
        ("position.assets", 2022): "299901040.54", ("position.equity", 2023): "187737473.46",
        ("performance.revenue", 2023): "136842656.46", ("performance.net_income", 2022): "23625441.70",
    },
    (2025, "Gattaran"): {
        ("position.assets", 2025): "1068248800.80", ("position.equity", 2024): "797743340.12",
        ("performance.revenue", 2025): "482695413.29", ("performance.net_income", 2024): "-6542951.65",
    },
    (2020, "Claveria"): {("position.equity", 2020): "308108347.93", ("position.equity", 2019): "219370031.29"},
    (2023, "Alcala"): {("position.equity", 2023): "501143393.29", ("performance.revenue", 2022): "246348606.41"},
    (2022, "Solana"): {("performance.expenses", 2022): "337236533.78", ("performance.expenses", 2021): "269208843.10"},
    (2025, "Amulung"): {("position.assets", 2025): "1061476967.29", ("position.assets", 2024): "820695165.21"},
    (2020, "Iguig"): {("performance.revenue", 2020): "139648447.21", ("performance.revenue", 2019): "115227409.44"},
    (2020, "Sta.-Praxedes"): {("position.assets", 2019): "116822544.28", ("performance.expenses", 2020): "58188869.14"},
}

# Checked to the cent against each town's audited workbook (data/towns/verified-finances-2024.md).
VERIFIED_2024 = {
    "Abulug": ("196960688.39", "179307711.01"),
    "Alcala": ("224744176.27", "201010574.32"),
    "Allacapan": ("232465478.52", "229812731.98"),
    "Tuguegarao-City": ("1465722146.22", "1184642113.21"),
}


@pytest.mark.parametrize(("report", "expected"), LAYOUTS.items(), ids=[f"{year}-{town}" for year, town in LAYOUTS])
def testReadsEachLayout(report, expected):
    found = values(parse(*report))

    assert {key: found.get(key) for key in expected} == {key: Decimal(value) for key, value in expected.items()}


@pytest.mark.parametrize("town", VERIFIED_2024)
def testReproducesVerified2024Figures(town):
    found = values(parse(2024, town))

    assert (str(found[("performance.revenue", 2024)]), str(found[("performance.expenses", 2024)])) == VERIFIED_2024[town]


def testMarksDefinitionVariants():
    variants = {figure.code: figure.variant for figure in parse(2020, "Sto.-Nino").figures}

    assert variants["performance.revenue"] == "includes_subsidy_from_other_funds"
    assert variants["performance.expenses"] == "includes_subsidy_to_other_funds"
    assert {figure.variant for figure in parse(2020, "Abulug").figures if figure.code == "budget.appropriations_current"} == {"unspecified_period"}


def testRepeatedHeaderYearFallsBackToReportAndPriorYear():
    result = parse(2020, "Sta.-Praxedes")

    assert {figure.fiscalYear for figure in result.figures} == {2019, 2020}
    assert "header_years_repeated" in {issue.kind for issue in result.issues}


def testBalanceAndNetIncomeHoldForACleanReport():
    result = parse(2024, "Tuguegarao-City")

    assert [issue for issue in documentChecks(result.figures, result.changes, 2024) if issue.severity == "error"] == []


def testFlagsSourceNetIncomeThatDoesNotAddUp():
    result = parse(2025, "Rizal")

    assert "net_income_mismatch" in {issue.kind for issue in documentChecks(result.figures, result.changes, 2025)}


def testReversedChangeColumnIsOnlyInformational():
    result = parse(2021, "Abulug")
    issues = documentChecks(result.figures, result.changes, 2021)

    assert {issue.kind for issue in issues} == {"change_sign_reversed"}
    assert {issue.severity for issue in issues} == {"info"}


def testFlagsSwappedColumnsAgainstThePreviousReport():
    reports = {2023: parse(2023, "Gattaran").figures, 2024: parse(2024, "Gattaran").figures}

    swapped = {issue.code for year, issue in restatementChecks(reports) if issue.kind == "repeats_previous_year"}

    assert {"position.assets", "performance.revenue", "performance.expenses"} <= swapped


def testReadsMislabelledColumnHeadersInPrintedOrder():
    # Gattaran 2024 heads its columns "2024 2023" but prints 2023 first; values read from page 2 by hand.
    earlier, result = parse(2023, "Gattaran").figures, parse(2024, "Gattaran")
    assert mislabelledYears(result.figures, result.changes, earlier, 2024)

    found = {(figure.code, figure.fiscalYear): figure.value for figure in swapYears(result.figures, 2024)}

    assert found[("performance.revenue", 2024)] == Decimal("401727932.51")
    assert found[("performance.expenses", 2024)] == Decimal("408270884.16")
    assert found[("performance.revenue", 2023)] == Decimal("382786319.24")


@pytest.mark.parametrize(("year", "town"), [(2021, "Abulug"), (2024, "Tuguegarao-City"), (2025, "Gattaran")])
def testCorrectlyLabelledColumnsAreNeverSwapped(year, town):
    result = parse(year, town)

    assert not mislabelledYears(result.figures, result.changes, parse(year - 1, town).figures, year)
