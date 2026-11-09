from decimal import Decimal
from pathlib import Path

import pytest

from coa.lines import load, match
from coa.numbers import TOKEN, parseAmount

LINES, ALIASES = load(Path(__file__).resolve().parents[3] / "data" / "towns" / "finances" / "lines.csv")


@pytest.mark.parametrize(
    ("token", "expected"),
    [
        ("₱4,909,278,026.68", Decimal("4909278026.68")),
        ("(18,619,155.28)", Decimal("-18619155.28")),
        ("P (294,252.42)", Decimal("-294252.42")),
        ("(₱38,608,400.23)", Decimal("-38608400.23")),
        ("269,208,843.1", Decimal("269208843.1")),
        ("-", None),
    ],
)
def testParsesAmountsAsPrinted(token, expected):
    assert parseAmount(token) == expected


def testPercentColumnIsNotReadAsAnAmount():
    assert len(TOKEN.findall("Assets ₱1,061,476,967.29 ₱820,695,165.21 129.34%")) == 2


@pytest.mark.parametrize(
    ("label", "code"),
    [
        ("Income", "performance.revenue"),
        ("Total Revenues", "performance.revenue"),
        ("Results of Operations Total Income (Includes Total Operating Income and Subsidy from Other Funds)", "performance.revenue"),
        ("Expense", "performance.expenses"),
        ("Govt. Equity", "position.equity"),
        ("Total Government Equity", "position.equity"),
        ("Net Assets/ Equity", "position.equity"),
        ("Appropriations – Current", "budget.appropriations_current"),
        ("Appropriations-Continuing", "budget.appropriations_continuing"),
        ("Allotment- Current", "budget.allotments_current"),
        ("Total Obligations - Current", "budget.obligations_current"),
        ("Surplus(Deficit)", "performance.net_income"),
    ],
)
def testMatchesLabelVariants(label, code):
    assert match(label, ALIASES, "executive_summary", LINES)[0] == code


@pytest.mark.parametrize("label", ["Insurance Expenses", "Due to NGAs", "Total", "Prior Years", "Equipment"])
def testIgnoresRowsOutsideTheCatalog(label):
    assert match(label, ALIASES, "executive_summary", LINES) is None
