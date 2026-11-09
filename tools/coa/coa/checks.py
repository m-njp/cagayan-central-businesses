from dataclasses import replace
from decimal import Decimal

from .summary import Figure, Issue

TOLERANCE = Decimal("1.00")


def documentChecks(figures: list[Figure], changes: dict[str, Decimal], reportYear: int) -> list[Issue]:
    """Arithmetic the summary must satisfy on its own; a failure blocks quick sign-off."""
    value = {(figure.code, figure.fiscalYear): figure.value for figure in figures}
    issues: list[Issue] = []
    for year in sorted({figure.fiscalYear for figure in figures}):
        identity(issues, value, year, "balance", "position.assets", ["position.liabilities", "position.equity"])
        net = [value.get(("performance.revenue", year)), value.get(("performance.expenses", year)), value.get(("performance.net_income", year))]
        if None not in net and abs(net[0] - net[1] - net[2]) > TOLERANCE:
            issues.append(Issue("error", "net_income_mismatch", "performance.net_income", f"{year}: revenue minus expenses is {net[0] - net[1]}, stated {net[2]}"))
    for code, stated in changes.items():
        current, prior = value.get((code, reportYear)), value.get((code, reportYear - 1))
        if current is None or prior is None or abs(current - prior - stated) <= TOLERANCE:
            continue
        # Some reports print prior minus current; the stored values are unaffected.
        if abs(current - prior + stated) <= TOLERANCE:
            issues.append(Issue("info", "change_sign_reversed", code, f"Report states the change as prior minus current ({stated})"))
        else:
            issues.append(Issue("warning", "change_mismatch", code, f"current minus prior is {current - prior}, stated {stated}"))
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


def swapYears(figures: list[Figure], reportYear: int) -> list[Figure]:
    other = {reportYear: reportYear - 1, reportYear - 1: reportYear}
    return [replace(figure, fiscalYear=other.get(figure.fiscalYear, figure.fiscalYear)) for figure in figures]


def identity(issues: list[Issue], value: dict, year: int, kind: str, total: str, parts: list[str]) -> None:
    amounts = [value.get((code, year)) for code in [total, *parts]]
    if None in amounts:
        return
    difference = amounts[0] - sum(amounts[1:])
    if abs(difference) > TOLERANCE:
        issues.append(Issue("error", f"{kind}_mismatch", total, f"{year}: {total} differs from {' + '.join(parts)} by {difference}"))


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
                    "error", "repeats_previous_year", figure.code, f"{reportYear} equals the {reportYear - 1} report's own {reportYear - 1} figure; columns may be swapped", getattr(figure, "page", None)
                )))
            elif figure.fiscalYear == reportYear - 1 and figure.code in earlier and earlier[figure.code].value != figure.value:
                issues.append((reportYear, Issue(
                    "info", "restated", figure.code, f"{figure.fiscalYear} was {earlier[figure.code].value} in the {reportYear - 1} report", getattr(figure, "page", None)
                )))
    return issues
