import logging
import re
from dataclasses import dataclass
from decimal import Decimal
from pathlib import Path

import pdfplumber
import pypdfium2

from .lines import Alias, Line, match
from .numbers import AMOUNT, DASH, PERCENT, TOKEN, parseAmount

START = re.compile(r"financial highlights|highlights of financial|financial information", re.I)
END = re.compile(
    r"operational highlights|independent auditor|auditor.s report|summary of (?:significant|audit|total)"
    r"|fund transfers|transferred funds|status of implementation",
    re.I,
)
HEADER_YEARS = re.compile(r"\b(20\d\d)\b[^\d]{1,40}\b(20\d\d)\b")
ROW = re.compile(
    rf"^(?P<label>.*?)\s*(?P<amounts>(?:(?:{AMOUNT}|{DASH})\s*){{2,4}})(?:\s*{PERCENT.pattern})*\s*$"
)
CORE = ("position.assets", "position.liabilities", "position.equity", "performance.revenue", "performance.expenses")
LABEL_LINES = 4
# pdfplumber keeps table rows on one line; pdfium is a separate text engine used to confirm the digits and as a fallback.
ENGINES = ("pdfplumber", "pdfium")

log = logging.getLogger("coa")


@dataclass(frozen=True)
class Figure:
    code: str
    fiscalYear: int
    value: Decimal
    variant: str | None
    sourceLabel: str
    page: int


@dataclass(frozen=True)
class Issue:
    severity: str
    kind: str
    code: str
    message: str
    page: int | None = None


@dataclass
class Result:
    figures: list[Figure]
    issues: list[Issue]
    changes: dict[str, Decimal]


def readLines(path: Path, engine: str = "pdfplumber") -> list[tuple[int, str]]:
    return [(number, line.strip()) for number, text in readPages(path, engine) for line in text.splitlines() if line.strip()]


def readPages(path: Path, engine: str) -> list[tuple[int, str]]:
    if engine == "pdfplumber":
        with pdfplumber.open(path) as pdf:
            return [(number, page.extract_text() or "") for number, page in enumerate(pdf.pages, start=1)]
    document = pypdfium2.PdfDocument(path)
    try:
        return [(number, document[number - 1].get_textpage().get_text_range()) for number in range(1, len(document) + 1)]
    finally:
        document.close()


def parseSummary(path: Path, reportYear: int, lines: dict[str, Line], aliases: list[Alias]) -> Result:
    """Read with pdfplumber; reread with pdfium only when pdfplumber misses a core line or fails outright."""
    try:
        primary = parseText(readLines(path, ENGINES[0]), reportYear, lines, aliases)
    except Exception as error:
        log.exception("%s: %s failed", path.name, ENGINES[0])
        primary = Result([], [Issue("error", "engine_failed", "", f"{ENGINES[0]}: {type(error).__name__}: {error}")], {})
    if not missingCore(primary):
        return confirmDigits(primary, path, ENGINES[1])

    log.warning("%s: %s missed %s, retrying with %s", path.name, ENGINES[0], ", ".join(missingCore(primary)) or "the table", ENGINES[1])
    try:
        fallback = parseText(readLines(path, ENGINES[1]), reportYear, lines, aliases)
    except Exception as error:
        log.exception("%s: %s failed", path.name, ENGINES[1])
        primary.issues.append(Issue("error", "engine_failed", "", f"{ENGINES[1]}: {type(error).__name__}: {error}"))
        return primary
    if len(missingCore(fallback)) >= len(missingCore(primary)):
        log.warning("%s: %s found no more core lines; keeping the %s reading", path.name, ENGINES[1], ENGINES[0])
        return confirmDigits(primary, path, ENGINES[1])
    log.warning("%s: read with the fallback engine %s", path.name, ENGINES[1])
    fallback.issues += [issue for issue in primary.issues if issue.kind == "engine_failed"]
    fallback.issues.append(Issue("warning", "read_with_fallback_engine", "", f"{ENGINES[0]} missed core lines; values read with {ENGINES[1]}"))
    return confirmDigits(fallback, path, ENGINES[0])


def missingCore(result: Result) -> list[str]:
    foundCodes = {figure.code for figure in result.figures}
    return [code for code in CORE if code not in foundCodes]


def confirmDigits(result: Result, path: Path, engine: str) -> Result:
    """Every value must also be printed on its page as the other engine reads it. This proves digits, not rows."""
    try:
        pages = {number: re.sub(r"\s+", "", text) for number, text in readPages(path, engine)}
    except Exception as error:
        log.exception("%s: %s could not confirm the digits", path.name, engine)
        result.issues.append(Issue("warning", "digits_unconfirmed", "", f"{engine}: {type(error).__name__}: {error}"))
        return result
    for figure in result.figures:
        printed = f"{abs(figure.value):,.2f}"
        candidates = (printed, printed[:-1]) if printed.endswith("0") else (printed,)
        if not any(candidate in pages.get(figure.page, "") for candidate in candidates):
            log.error("%s: %s %s %s is not on page %s in %s", path.name, figure.code, figure.fiscalYear, printed, figure.page, engine)
            result.issues.append(Issue("error", "engine_disagreement", figure.code, f"{figure.fiscalYear}: {printed} is not on page {figure.page} as {engine} reads it", figure.page))
    return result


def parseText(text: list[tuple[int, str]], reportYear: int, lines: dict[str, Line], aliases: list[Alias]) -> Result:
    issues: list[Issue] = []
    start = next((index for index, (_, line) in enumerate(text) if START.search(line)), None)
    if start is None:
        return Result([], [Issue("error", "no_highlights_section", "", "No financial highlights heading found")], {})

    years: tuple[int, int] | None = None
    found: dict[str, tuple[list[Decimal | None], str | None, str, int]] = {}
    buffer: list[str] = []
    hasEnded = False

    region = text[start + 1 :]
    for index, (page, line) in enumerate(region):
        if not hasEnded and END.search(line) and found:
            hasEnded = True
        if hasEnded and all(code in found for code in CORE):
            break
        if years is None and not found and (header := HEADER_YEARS.search(line)):
            years = (int(header.group(1)), int(header.group(2)))
            buffer = []
            continue
        row = ROW.match(line)
        if not row:
            buffer = (buffer + [line])[-LABEL_LINES:]
            continue
        amounts = [parseAmount(token) for token in TOKEN.findall(row.group("amounts"))]
        following = []
        for _, after in region[index + 1 : index + 1 + LABEL_LINES]:
            if ROW.match(after):
                break
            following.append(after)
        hit = labelMatch(row.group("label"), buffer, following, lines, aliases)
        buffer = []
        if hit is None:
            continue
        code, variant, label = hit
        if hasEnded and code not in CORE:
            continue
        if hasEnded:
            issues.append(Issue("warning", "found_after_section_end", code, "Row read after the next section heading", page))
        if code in found:
            if found[code][0][:2] != amounts[:2]:
                issues.append(Issue("error", "conflicting_values", code, f"Second row '{label}' disagrees with the first", page))
            continue
        found[code] = (amounts, variant, label, page)

    if years is None or years[0] == years[1]:
        kind = "header_years_missing" if years is None else "header_years_repeated"
        issues.append(Issue("warning", kind, "", "Column years unreadable; assumed current year then prior year"))
        years = (reportYear, reportYear - 1)
    if sorted(years) != [reportYear - 1, reportYear]:
        issues.append(Issue("error", "header_year_mismatch", "", f"Columns are {years[0]} and {years[1]}, report year is {reportYear}"))

    figures: list[Figure] = []
    changes: dict[str, Decimal] = {}
    for code, (amounts, variant, label, page) in found.items():
        for year, value in zip(years, amounts[:2]):
            if value is not None:
                figures.append(Figure(code, year, value, variant, label, page))
        if len(amounts) > 2 and amounts[2] is not None:
            changes[code] = amounts[2]
    for code in CORE:
        if code not in found:
            issues.append(Issue("warning", "line_not_found", code, f"{lines[code].label} not found"))
    return Result(figures, issues, changes)


def labelMatch(sameLine: str, buffer: list[str], following: list[str], lines: dict[str, Line], aliases: list[Alias]):
    """Try the row's label with wrapped lines above it, longest first, then with the line below.

    Some tables center the numbers in a two-line cell, so the label reads "Government", numbers, "Equity".
    """
    candidates = [" ".join(buffer[index:] + [sameLine]).strip() for index in range(len(buffer))]
    candidates.append(sameLine.strip())
    if following:
        candidates += [f"{candidate} {following[0]}" for candidate in candidates[-2:]]
    for candidate in candidates:
        if candidate and (hit := match(candidate, aliases, "executive_summary", lines)):
            # A qualifier left open, "(Includes Subsidy from", continues below the numbers.
            for count in range(1, len(following) + 1):
                if candidate.count("(") <= candidate.count(")"):
                    break
                longer = " ".join([candidate, *following[:count]])
                if longer.count("(") <= longer.count(")") and (completed := match(longer, aliases, "executive_summary", lines)):
                    return completed[0], completed[1], longer
            # "Appropriations" with "– Current" below the numbers (Tuguegarao 2021, 2022) is not an unspecified period.
            if hit[1] == "unspecified_period" and following:
                longer = f"{candidate} {following[0]}"
                if (completed := match(longer, aliases, "executive_summary", lines)) and completed[0] == hit[0] and completed[1] is None:
                    return completed[0], completed[1], longer
            return hit[0], hit[1], candidate
    return None
