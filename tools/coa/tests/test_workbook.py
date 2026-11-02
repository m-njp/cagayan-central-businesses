from decimal import Decimal
from pathlib import Path

import pytest

from coa.lines import load
from coa.workbook import readAnnex, readFs

ROOT = Path(__file__).resolve().parents[3]
WORKBOOKS = ROOT / "data" / "towns" / "financial-statements"
LINES, ALIASES = load(ROOT / "data" / "towns" / "finances" / "lines.csv")


def read(year: int, folder: str, kind: str):
    reader = readFs if kind == "FS" else readAnnex
    return reader(WORKBOOKS / str(year) / folder / f"{folder}-{kind}-{year}.xlsx", year, LINES, ALIASES)


def values(result) -> dict[tuple[str, int, str], Decimal]:
    return {(figure.code, figure.fiscalYear, figure.basis): figure.value for figure in result.figures}


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
