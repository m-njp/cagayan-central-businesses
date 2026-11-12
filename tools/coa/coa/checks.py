from dataclasses import replace
from decimal import Decimal

from .lines import lineLabel
from .numbers import peso
from .summary import Figure, Issue

TOLERANCE = Decimal("1.00")


def documentChecks(figures: list[Figure], changes: dict[str, Decimal], reportYear: int) -> list[Issue]:
    """Flag source arithmetic for the reviewer without changing the printed amounts."""
    value = {(figure.code, figure.fiscalYear): figure.value for figure in figures}
    pages = {(figure.code, figure.fiscalYear): figure.page for figure in figures}
    issues: list[Issue] = []
    for year in sorted({figure.fiscalYear for figure in figures}):
        identity(issues, value, year, "balance", "position.assets", ["position.liabilities", "position.equity"], pages.get(('position.assets', year)))
        net = [value.get(("performance.revenue", year)), value.get(("performance.expenses", year)), value.get(("performance.net_income", year))]
        if None not in net and abs(net[0] - net[1] - net[2]) > TOLERANCE:
            issues.append(Issue("error", "net_income_mismatch", "performance.net_income", f"{year}: Income less Expenses is {peso(net[0] - net[1])}, but the report shows Net Income of {peso(net[2])}.", pages.get(('performance.net_income', year)), year))
    for code, stated in changes.items():
        current, prior = value.get((code, reportYear)), value.get((code, reportYear - 1))
        if current is None or prior is None or abs(current - prior - stated) <= TOLERANCE:
            continue
        # Some reports print prior minus current; the stored values are unaffected.
        if abs(current - prior + stated) <= TOLERANCE:
            issues.append(Issue("info", "change_sign_reversed", code, f"{lineLabel(code)}: the Increase (Decrease) column shows {peso(stated)}, which is the {reportYear - 1} amount less the {reportYear} amount rather than the reverse.", pages.get((code, reportYear)), reportYear))
        else:
            issues.append(Issue("warning", "change_mismatch", code, f"{lineLabel(code)}: the Increase (Decrease) column shows {peso(stated)}, but the {reportYear} amount less the {reportYear - 1} amount is {peso(current - prior)}.", pages.get((code, reportYear)), reportYear))
    return issues


def mislabelledYears(figures: list[Figure], changes: dict[str, Decimal], earlier: list[Figure], reportYear: int) -> bool:
    """Columns headed current then prior but printed prior then current (Gattaran 2024).

    Both proofs must hold for revenue and expenses: the column headed with the report year repeats the
    previous report's own figure to the cent, and the stated change only adds up the other way round.
    """
    value = {(figure.code, figure.fiscalYear): figure.value for figure in figures}
    own = {figure.code: figure.value for figure in earlier if figure.fiscalYear == reportYear - 1}
    for code in ("performance.revenue", "performance.expenses"):
        headedCurrent, headedPrior, stated = value.get((code, reportYear)), value.get((code, reportYear - 1)), changes.get(code)
        if headedCurrent is None or headedPrior is None or stated is None or own.get(code) != headedCurrent:
            return False
        if abs(headedPrior - headedCurrent - stated) > TOLERANCE:
            return False
    return True


def staleHeaderFigures(evidence: list[dict], earlier: list[Figure], reportYear: int, lines, aliases) -> list[Figure]:
    """Rows withheld under a second header one year behind the table's (Alcala 2021 heads its budget rows "2020 2019").

    They belong to the report's own years only when the previous report proves it: every withheld row it also prints
    has its second column equal to that report's own figure, its first column differs, and at least one row matches.
    """
    from .lines import match
    from .numbers import parseAmount

    own = {figure.code: figure.value for figure in earlier if figure.fiscalYear == reportYear - 1}
    rows = {}
    for item in evidence:
        if item.get("status") == "withheld" and item.get("tokens") and item.get("printed_years") == [reportYear - 1, reportYear - 2]:
            rows.setdefault(item["line_code"], item)
    figures, proven = [], 0
    for code, item in rows.items():
        try:
            amounts = [parseAmount(token) for token in item["tokens"][:2]]
        except ValueError:
            return []
        hit = match(item["label"], aliases, "executive_summary", lines)
        if len(amounts) < 2 or hit is None or hit[0] != code:
            return []
        if code in own:
            if amounts[1] != own[code] or amounts[0] == own[code]:
                return []
            proven += 1
        figures += [restoredFigure(code, year, value, hit[1], item) for year, value in zip((reportYear, reportYear - 1), amounts) if value is not None]
    return figures if proven else []


def restoredFigure(code: str, year: int, value: Decimal, variant: str | None, item: dict) -> Figure:
    return Figure(code, year, value, variant, item["label"], item["page"])


def swapYears(figures: list[Figure], reportYear: int) -> list[Figure]:
    other = {reportYear: reportYear - 1, reportYear - 1: reportYear}
    return [replace(figure, fiscalYear=other.get(figure.fiscalYear, figure.fiscalYear)) for figure in figures]


def identity(issues: list[Issue], value: dict, year: int, kind: str, total: str, parts: list[str], page: int | None = None) -> None:
    amounts = [value.get((code, year)) for code in [total, *parts]]
    if None in amounts:
        return
    difference = amounts[0] - sum(amounts[1:])
    if abs(difference) > TOLERANCE:
        issues.append(Issue("error", f"{kind}_mismatch", total, f"{year}: Total Assets of {peso(amounts[0])} do not equal Total Liabilities plus Government Equity of {peso(sum(amounts[1:]))}, a difference of {peso(difference)}. The Statement of Financial Position should balance.", page, year))


def restatementChecks(byReport: dict[int, list[Figure]]) -> list[tuple[int, Issue]]:
    """Compare each report's prior-year column with the previous report's current column."""
    issues: list[tuple[int, Issue]] = []
    for reportYear, figures in byReport.items():
        earlier = {figure.code: figure for figure in byReport.get(reportYear - 1, []) if figure.fiscalYear == reportYear - 1}
        for figure in figures:
            # A current-year figure equal to last year's own figure usually means swapped column headers.
            # Sector budgets such as Aid to Barangays are often the same fixed amount every year.
            repeats = figure.fiscalYear == reportYear and figure.code in earlier and earlier[figure.code].value == figure.value
            if repeats and figure.value != 0 and not figure.code.startswith("sector."):
                issues.append((reportYear, Issue(
                    "error", "repeats_previous_year", figure.code, f"{lineLabel(figure.code)}: the {reportYear} column shows {peso(figure.value)}, the same amount the {reportYear - 1} report shows for {reportYear - 1}. Either the column headings are reversed or the {reportYear - 1} amount was carried forward; confirm on the page.", getattr(figure, "page", None), figure.fiscalYear
                )))
            elif figure.fiscalYear == reportYear - 1 and figure.code in earlier and earlier[figure.code].value != figure.value:
                issues.append((reportYear, Issue(
                    "info", "restated", figure.code, f"{lineLabel(figure.code)} for {figure.fiscalYear}: the {reportYear - 1} report shows {peso(earlier[figure.code].value)}, while this {reportYear} report shows {peso(figure.value)} in its prior-year column, a difference of {peso(figure.value - earlier[figure.code].value)}. The later report prevails.", getattr(figure, "page", None), figure.fiscalYear
                )))
    return issues
