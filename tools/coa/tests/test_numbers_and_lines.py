from decimal import Decimal, localcontext
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
        ("12345678.90", Decimal("12345678.90")),
        ("P=123,456.78", Decimal("123456.78")),
        ("₱ (123,456.78)", Decimal("-123456.78")),
        ("-₱123,456.78", Decimal("-123456.78")),
        ("₱-123,456.78", Decimal("-123456.78")),
        ("P - 123,456.78", Decimal("-123456.78")),
        ("−123,456.78", Decimal("-123456.78")),
        ("− 123,456.78", Decimal("-123456.78")),
        ("P= −123,456.78", Decimal("-123456.78")),
        ("−₱123,456.78", Decimal("-123456.78")),
        ("0.00", Decimal("0.00")),
        ("(0.00)", Decimal("-0.00")),
        ("-", None),
    ],
)
def testParsesAmountsAsPrinted(token, expected):
    assert parseAmount(token) == expected


def testPercentColumnIsNotReadAsAnAmount():
    assert len(TOKEN.findall("Assets ₱1,061,476,967.29 ₱820,695,165.21 129.34%")) == 2


@pytest.mark.parametrize("token", [
    "(123,456.78", "123,456.78)", "((123,456.78))", "(-123,456.78)",
    "₱ (123,456.78", "(₱ 123,456.78", "₱123,456.78)", "₱₱123,456.78",
    "123,45.67", "1234,567.89", "48,014,545,75", "21,468355.65", "1,,234.56",
    "123,456.", "123,456.789", ".50", "1e3.00", "NaN", "Infinity",
    "123.45%", "(123.45%)", "123.45 %", "$123.45", "USD123.45", "P==123.45",
    "+-123.45", "--123.45", "123.45-", "123.45abc", "abc123.45", "(-)",
    "−", "–", "—", "–123.45", "—123.45", "– 123.45", "— 123.45", "(−123.45)",
])
def testRejectsMalformedWholeMonetaryTokensWithoutRepairingThem(token):
    with pytest.raises(ValueError, match="Malformed monetary token"):
        parseAmount(token)


@pytest.mark.parametrize("token", [
    "(123,456.78", "123,456.78)", "((123,456.78))", "(-123,456.78)",
    "₱ (123,456.78", "(₱ 123,456.78", "₱123,456.78)", "₱₱123,456.78",
    "123,45.67", "1234,567.89", "48,014,545,75", "21,468355.65", "1,,234.56",
    "123,456.", "123,456.789", ".50", "1e3.00", "123.45%", "(123.45%)",
    "123.45 %", "$123.45", "USD123.45", "P==123.45", "+-123.45", "--123.45",
    "123.45-", "123.45abc", "abc123.45", "(-)",
    "−", "–", "—", "–123.45", "—123.45", "– 123.45", "— 123.45", "(−123.45)",
])
def testDoesNotSalvageValidLookingSubstringsFromMalformedCells(token):
    assert TOKEN.findall(token) == []


def testTokenExtractionPreservesAccountingSignsAndSeparateCells():
    tokens = TOKEN.findall("Income ₱123,456.78 P (100,000.00) -23,456.78 18.25%")
    assert [parseAmount(token) for token in tokens] == [Decimal("123456.78"), Decimal("-100000.00"), Decimal("-23456.78")]


def testPrintedDashIsNotJoinedToTheNextFiscalColumnAsANegativeAmount():
    assert [parseAmount(token) for token in TOKEN.findall("Assets - 90.00 10.00")] == [None, Decimal("90.00"), Decimal("10.00")]
    with pytest.raises(ValueError, match="Malformed monetary token"):
        parseAmount("- 90.00")


def testParsingDoesNotRoundNegativeAmountsThroughTheAmbientDecimalContext():
    with localcontext() as context:
        context.prec = 3
        assert parseAmount("(123,456.78)") == Decimal("-123456.78")
        assert parseAmount("P= −123,456.78") == Decimal("-123456.78")
    assert parseAmount("(0.00)").as_tuple().sign == 1


def testUnicodeMinusRemainsInTheRawTokenAndNeverBecomesAPositiveAmount():
    tokens = TOKEN.findall("Income P= −123,456.78 −100,000.00 −23,456.78 −19.00%")
    assert tokens == ["P= −123,456.78", "−100,000.00", "−23,456.78"]
    assert [parseAmount(token) for token in tokens] == [Decimal("-123456.78"), Decimal("-100000.00"), Decimal("-23456.78")]


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
