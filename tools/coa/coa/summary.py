import logging
import re
import unicodedata
from collections import defaultdict
from dataclasses import dataclass, field
from decimal import Decimal
from pathlib import Path

import pdfplumber
import pypdfium2

from .lines import Alias, Line, lineLabel, match
from .numbers import AMOUNT, DASH, PERCENT, TOKEN, hasMisprintedAmount, joinSplitDigits, parseAmount, peso
from .progress import Progress, preview, report

START = re.compile(r"financial highlights|highlights of financial|financial information", re.I)
END = re.compile(
    r"operational highlights|independent auditor|auditor.s report|summary of (?:significant|audit|total)"
    r"|fund transfers|transferred funds|status of implementation",
    re.I,
)
HEADER_YEARS = re.compile(r"\b(20\d\d)\b[^\d]{1,40}\b(20\d\d)\b")
ROW = re.compile(
    rf"^(?P<label>.*?)\s*(?P<amounts>(?:(?:{AMOUNT}|{DASH})\s*){{2,4}})(?P<trailing>[\d\s,().%+-]*)$"
)
CORE = ("position.assets", "position.liabilities", "position.equity", "performance.revenue", "performance.expenses")
# Older Gattaran summaries wrap the expense label and its qualifier across six lines.
LABEL_LINES = 8
# pdfplumber keeps table rows on one line; pdfium is a separate text engine used to confirm the digits and as a fallback.
ENGINES = ("pdfplumber", "pdfium")

log = logging.getLogger("coa")

# Who an issue is about. Anything that may be the parser's own limitation is "reading", never the government's report.
FAMILIES = {
    "reading": (
        "ambiguous_sheets", "budget_columns_missing", "checksum_mismatch", "digits_unconfirmed", "engine_disagreement",
        "engine_failed", "header_years_missing", "heading_not_in_catalog", "incomplete_components", "incomplete_row", "line_not_found",
        "municipality_unconfirmed", "national_share_not_separate", "no_financial_rows", "no_highlights_section",
        "no_text_layer", "page_without_text", "parser_failed", "province_unconfirmed", "read_with_fallback_engine",
        "sheet_not_found", "spending_outside_catalog", "unreadable_label", "unsupported_fund_scope", "unsupported_units",
        "year_columns_ambiguous", "year_columns_missing",
    ),
    "report": (
        "balance_mismatch", "change_mismatch", "change_sign_reversed", "column_order_changed", "conflicting_values",
        "document_year_mismatch", "header_year_context_changed", "header_year_mismatch", "header_years_repeated", "header_years_stale",
        "identity_text_conflict", "malformed_amount", "municipality_mismatch", "net_income_mismatch", "province_mismatch",
        "revenue_sources_mismatch", "sheet_year_mismatch", "unreadable_change", "value_not_reported",
    ),
    "between_reports": ("differs_from_summary", "header_years_swapped", "repeats_previous_year", "restated"),
}
FAMILY = {kind: family for family, kinds in FAMILIES.items() for kind in kinds}


def family(kind: str) -> str:
    return FAMILY.get(kind, "reading")


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
    fiscalYear: int | None = None


@dataclass
class Result:
    figures: list[Figure]
    issues: list[Issue]
    changes: dict[str, Decimal]
    evidence: list[dict] = field(default_factory=list)


def readLines(path: Path, engine: str = "pdfplumber") -> list[tuple[int, str]]:
    return [(number, line.strip()) for number, text in readPages(path, engine) for line in text.splitlines() if line.strip()]


def readPages(path: Path, engine: str, progress: Progress = None) -> list[tuple[int, str]]:
    pages = []
    if engine == "pdfplumber":
        with pdfplumber.open(path) as pdf:
            for number, page in enumerate(pdf.pages, start=1):
                report(progress, "scan", stage="scanning", message=f"Reading page {number} of {len(pdf.pages)}.", location=f"page {number}", current=number, total=len(pdf.pages), engine=engine)
                pages.append((number, page.extract_text() or ""))
                page.close()
        return pages
    document = pypdfium2.PdfDocument(path)
    try:
        for number in range(1, len(document) + 1):
            report(progress, "scan", stage="checking", message=f"Confirming digits on page {number} of {len(document)}.", location=f"page {number}", current=number, total=len(document), engine=engine)
            page = document[number - 1]
            try:
                textPage = page.get_textpage()
                try:
                    # PDFium marks a soft line-break hyphen as U+FFFE ("Appropriations\ufffeContinuing", Gattaran 2020).
                    pages.append((number, textPage.get_text_range().replace("\ufffe", "-").replace("\u00ad", "-")))
                finally:
                    textPage.close()
            finally:
                page.close()
        return pages
    finally:
        document.close()


def parseSummary(path: Path, reportYear: int, lines: dict[str, Line], aliases: list[Alias], progress: Progress = None, expectedTown: str | None = None, expectedProvince: str | None = None) -> Result:
    """Read with pdfplumber; reread with pdfium only when pdfplumber misses a core line or fails outright."""
    pageCache: dict[str, list[tuple[int, str]] | Exception] = {}

    def rawPages(engine: str) -> list[tuple[int, str]]:
        if engine not in pageCache:
            try:
                pageCache[engine] = readPages(path, engine) if progress is None else readPages(path, engine, progress)
            except Exception as error:
                pageCache[engine] = error
        cached = pageCache[engine]
        if isinstance(cached, Exception):
            raise cached
        return cached

    def cachedPages(engine: str) -> list[tuple[int, str]]:
        """A digit spaced off its group is joined only where the other reader prints the joined amount on that page."""
        try:
            other = dict(rawPages(ENGINES[1] if engine == ENGINES[0] else ENGINES[0]))
        except Exception:
            other = {}
        return [(number, joinSplitDigits(text, other.get(number, ""))) for number, text in rawPages(engine)]

    def parseEngine(engine: str) -> Result:
        pages = cachedPages(engine)
        if not any(page.strip() for _, page in pages):
            return Result([], [Issue("error", "no_text_layer", "", "The PDF has no readable text; it appears to be a scanned image. The amounts must be entered by a reviewer from the page.", 1)], {})
        text = [(number, line.strip()) for number, page in pages for line in page.splitlines() if line.strip()]
        result = parseText(text, reportYear, lines, aliases, progress if engine == ENGINES[0] else None)
        result.issues.extend(Issue("warning", "page_without_text", "", f"Page {number} has no readable text. It may be blank or a scanned image; check the page for any amounts.", number) for number, page in pages if not page.strip())
        identityIssues = documentContext(pages, expectedTown, expectedProvince)
        result.issues.extend(identityIssues)
        if any(issue.severity == 'error' for issue in identityIssues):
            result.figures = []
        for item in result.evidence:
            item['engine'] = engine
        return result

    try:
        primary = parseEngine(ENGINES[0])
    except Exception as error:
        log.exception("%s: %s failed", path.name, ENGINES[0])
        primary = Result([], [Issue("error", "engine_failed", "", f"{ENGINES[0]}: {type(error).__name__}: {error}")], {})
    # A printing error in the report (Alcala 2018 "101,724,726,81") is not a reading failure; another engine cannot fix it.
    if not missingCore(primary, reportYear) and not readingErrors(primary):
        report(progress, "stage", stage="checking", message="Confirming digits with the second PDF reader.")
        return confirmDigits(primary, path, ENGINES[1], cachedPages, reportYear, lines, aliases)

    log.warning("%s: %s missed %s, retrying with %s", path.name, ENGINES[0], ", ".join(missingCore(primary, reportYear)) or "the table", ENGINES[1])
    report(progress, "stage", stage="checking", message="Checking missing lines with the fallback PDF reader.")
    try:
        fallback = parseEngine(ENGINES[1])
    except Exception as error:
        log.exception("%s: %s failed", path.name, ENGINES[1])
        primary.issues.append(Issue("error", "engine_failed", "", f"{ENGINES[1]}: {type(error).__name__}: {error}"))
        return primary
    if readingQuality(fallback, reportYear) >= readingQuality(primary, reportYear):
        log.warning("%s: %s found no more core lines; keeping the %s reading", path.name, ENGINES[1], ENGINES[0])
        return confirmDigits(primary, path, ENGINES[1], cachedPages, reportYear, lines, aliases)
    log.warning("%s: read with the fallback engine %s", path.name, ENGINES[1])
    fallback.issues += [issue for issue in primary.issues if issue.kind == "engine_failed"]
    fallback.issues.append(Issue("warning", "read_with_fallback_engine", "", "The first PDF reader could not read the full financial highlights table, so the amounts were taken from the second reader and confirmed against the first."))
    return confirmDigits(fallback, path, ENGINES[0], cachedPages, reportYear, lines, aliases)


def townWords(text: str) -> str:
    text = unicodedata.normalize('NFKD', text).encode('ascii', 'ignore').decode().lower()
    text = re.sub(r'\bsta\b', 'santa', text)
    text = re.sub(r'\bsto\b', 'santo', text)
    return re.sub(r'[^a-z]+', ' ', text).strip()


def documentContext(pages: list[tuple[int, str]], expectedTown: str | None, expectedProvince: str | None = None) -> list[Issue]:
    if not expectedTown:
        return []
    earlyPages = [(number, text[:8000]) for number, text in pages if number <= 3]
    identities = []
    for number, text in earlyPages:
        header = re.sub(r'\s+', ' ', text)
        for match in re.finditer(r'(?=\b(?:municipality|(?:municipal|city)\s+government|city)\s+of\s+(.{1,250}))', header, re.I):
            before = header[max(0, match.start() - 100):match.start()]
            identities.append((number, match[1], re.search(r'audit covered|accounts and operations|financial statements of', before, re.I) is not None))
    words = townWords(expectedTown).removesuffix(' city').split()
    pattern = r'^' + r'\s*'.join(re.escape(word) for word in words) + r'\b'
    auditIdentities = [item for item in identities if item[2]]
    conflict = []
    if auditIdentities:
        matched = [item for item in auditIdentities if words and re.match(pattern, townWords(item[1]))]
        # Lasam 2017 opens "The Municipality of Lasam", then says "the audit covered ... the Municipality of Abulug".
        # Only the introduction's own subject counts, never a neighbor it mentions later.
        introduction = next((item for item in identities if not item[2]), None)
        others = [introduction] if introduction and words and re.match(pattern, townWords(introduction[1])) else []
        if not matched and others:
            other = re.split(r'[.;,\r\n]| for the year', auditIdentities[0][1], maxsplit=1)[0].strip()
            conflict = [Issue('warning', 'identity_text_conflict', '', f'The title and introduction describe {expectedTown}, but the audit scope paragraph names {other}. The figures are kept under {expectedTown}; the scope paragraph appears to be carried over from another report.', auditIdentities[0][0])]
            matched = others
        if not conflict:
            identities = auditIdentities
    else:
        matched = [item for item in identities if words and re.match(pattern, townWords(item[1]))]
    if matched:
        if expectedProvince:
            provincePattern = r'\b' + re.escape(townWords(expectedProvince)) + r'\b'
            # Province headers precede the government; later geographic neighbors are not its identity.
            identityHeaders = []
            for _, text in earlyPages:
                headerLines = text.splitlines()
                governmentLine = next((index for index, line in enumerate(headerLines)
                                       if re.search(r'\b(?:municipality|city|government)\s+of\b', line, re.I)), 0)
                identityHeaders.extend(headerLines[:governmentLine])
            identityText = '\n'.join(identityHeaders)
            identityText += '\n' + '\n'.join(re.split(r'[.;\r\n]', item[1], maxsplit=1)[0] for item in matched)
            explicit = re.search(r'\bprovince\s+of\s+([^\r\n,;.]{1,80})', identityText, re.I)
            if explicit and not re.match(provincePattern, townWords(explicit[1])):
                return conflict + [Issue('error', 'province_mismatch', '', f'The report names a province other than {expectedProvince}. A municipality of the same name may exist elsewhere; the figures are held back until the source is confirmed.', matched[0][0])]
            if explicit is None and not any(re.search(provincePattern, townWords(item[1])) for item in matched):
                return conflict + [Issue('warning', 'province_unconfirmed', '', f'The opening pages name the municipality but not the Province of {expectedProvince}. The report was matched by municipality name and its entry in the source register.', matched[0][0])]
        return conflict
    # Some city reports print the municipality as a standalone title before Executive Summary.
    firstPage = next((text for number, text in pages if number == 1), '')
    title = ' '.join(firstPage.splitlines()[:12])
    if not identities and words and re.search(r'\b' + pattern.removeprefix('^'), townWords(title)) and re.search(r'executive summary', title, re.I):
        if expectedProvince and not re.search(r'\b' + re.escape(townWords(expectedProvince)) + r'\b', townWords(title)):
            return [Issue('warning', 'province_unconfirmed', '', f'The report title names the municipality but not the Province of {expectedProvince}. The report was matched by municipality name and its entry in the source register.', 1)]
        return []
    kind = 'municipality_mismatch' if identities else 'municipality_unconfirmed'
    return [Issue('error' if identities else 'warning', kind, '', (f'The opening pages name a different local government, not {expectedTown}. The figures are held back until the source is confirmed.' if identities else f'The opening pages do not name {expectedTown}. The report was matched by its entry in the source register; confirm the municipality on the page.'), identities[0][0] if identities else 1)]


def unsupportedUnits(text: str) -> bool:
    return re.search(r"\bin\s*\(?\s*(?:(?:PHP|P|₱)\s*)?(?:thousands?|millions?|billions?|USD|US dollars|EUR)\b"
                     r"|\b(?:in|amounts?)\s*[:(]?\s*(?:PHP|P|₱)?\s*['’]?000(?:['’]?s)?\b"
                     r"|(?:₱|PHP|P)\s*(?:['’]000|\(000\))(?![0-9])", text, re.I) is not None


def missingCore(result: Result, reportYear: int | None = None) -> list[str]:
    found = {(figure.code, figure.fiscalYear) for figure in result.figures}
    if reportYear is None:
        foundCodes = {code for code, _ in found}
        return [code for code in CORE if code not in foundCodes]
    return [f"{code} {year}" for code in CORE for year in (reportYear, reportYear - 1) if (code, year) not in found]


def readingErrors(result: Result) -> int:
    return sum(issue.severity == "error" and family(issue.kind) == "reading" for issue in result.issues)


def readingQuality(result: Result, reportYear: int) -> tuple[int, int, int]:
    """Lower is better: missing core lines, then fewer figures read, then reading errors (Alcala 2022: one split row
    must not swap 16 figures for the other reader's 10)."""
    return (len(missingCore(result, reportYear)), -len(result.figures), readingErrors(result))


def confirmDigits(result: Result, path: Path, engine: str, pageReader=None, reportYear=None, lines=None, aliases=None) -> Result:
    """The other reader must agree on line, fiscal column, sign, definition and page wherever it reads a row whole: its
    label and amounts on one line. Where it splits a row instead (pdfium prints Iguig 2017's amounts on the lines below
    each label, and ends Gattaran 2020's section early), the row's signed amounts must still be printed within three
    lines of that page, starting at a line whose label, with any wrapped lines above it, names the same catalog line."""
    try:
        sourcePages = pageReader(engine) if pageReader else readPages(path, engine)
        if lines is None or aliases is None:
            from .lines import load
            lines, aliases = load(Path(__file__).resolve().parents[3] / "data/towns/finances/lines.csv")
        reportYear = reportYear or max((figure.fiscalYear for figure in result.figures), default=0)
        reference = parseText([(number, line.strip()) for number, text in sourcePages for line in text.splitlines() if line.strip()], reportYear, lines, aliases)
    except Exception as error:
        log.exception("%s: %s could not confirm the digits", path.name, engine)
        result.issues.append(Issue("error", "digits_unconfirmed", "", "The second PDF reader could not open the report, so no amount on it could be confirmed independently."))
        return result
    for item in reference.evidence:
        result.evidence.append({**item, 'engine': engine})
    pageLines = {number: [line.strip() for line in text.splitlines() if line.strip()] for number, text in sourcePages}
    wholeRows = {item["line_code"] for item in reference.evidence if item.get("status") == "candidate" and readWhole(item, lines, aliases)}
    otherReading = {(figure.code, figure.fiscalYear): figure for figure in reference.figures if figure.code in wholeRows}
    rowValues = defaultdict(set)
    for figure in result.figures:
        rowValues[(figure.code, figure.page)].add(figure.value)
    for figure in result.figures:
        other = otherReading.get((figure.code, figure.fiscalYear))
        if other is not None:
            if (other.value, other.variant, other.page) == (figure.value, figure.variant, figure.page):
                continue
            problem = (f"the second reader finds a different description in the line label on page {other.page} (for example, whether subsidies are included)" if other.value == figure.value
                       else f"the second reader reads {peso(other.value)} on page {other.page}")
        elif printedRow(pageLines.get(figure.page, []), figure.code, rowValues[(figure.code, figure.page)], lines, aliases):
            continue
        else:
            problem = f"the second reader does not find this amount on the {lines[figure.code].label} line of that page"
        result.issues.append(Issue("error", "engine_disagreement", figure.code,
            f"{lines[figure.code].label} for {figure.fiscalYear}: the first PDF reader reads {peso(figure.value)} on page {figure.page}, but {problem}. The PDF text may be damaged or misaligned; confirm the amount on the page.", figure.page, figure.fiscalYear))
    return result


def readWhole(item: dict, lines: dict[str, Line], aliases: list[Alias]) -> bool:
    """The other reader's row carries its own label on the line with its amounts."""
    number = re.search(r"[0-9₱$€£(]|P=?\s*[(−-]?[0-9]", item["raw"])
    label = item["raw"][:number.start()].rstrip(" (−–—-") if number else ""
    hit = match(label, aliases, "executive_summary", lines) if label else None
    return hit is not None and hit[0] == item["line_code"]


def printedRow(pageText: list[str], code: str, values: set[Decimal], lines: dict[str, Line], aliases: list[Alias]) -> bool:
    for index, line in enumerate(pageText):
        if not values <= set().union(*(lineAmounts(nearby) for nearby in pageText[index:index + 3])):
            continue
        number = re.search(r"[0-9₱$€£(]|P=?\s*[(−-]?[0-9]", line)
        label = line[:number.start()] if number else line
        hit = labelMatch(label.rstrip(" (−–—-"), pageText[max(0, index - LABEL_LINES):index], pageText[index + 1:index + 2], lines, aliases)
        # An "Appropriations" line without its qualifier still carries the Current or Continuing amounts.
        if hit is not None and (hit[0] == code or hit[1] == "unspecified_period" and hit[0].rsplit("_", 1)[0] == code.rsplit("_", 1)[0]):
            return True
    return False


def lineAmounts(line: str) -> set[Decimal]:
    amounts = set()
    for token in TOKEN.findall(line):
        try:
            if (value := parseAmount(token)) is not None:
                amounts.add(value)
        except ValueError:
            continue
    return amounts


def parseText(text: list[tuple[int, str]], reportYear: int, lines: dict[str, Line], aliases: list[Alias], progress: Progress = None) -> Result:
    """Read the first highlights section that has rows. Lal-Lo 2014 lists "Financial Highlights" on page 1 right
    before "Operational Highlights"; its table follows a second heading on page 2."""
    starts = [index for index, (_, line) in enumerate(text) if START.search(line)]
    if not starts:
        return Result([], [Issue("error", "no_highlights_section", "", "No Financial Highlights table was found in the report. The amounts must be entered by a reviewer from the page.")], {})
    first = None
    for start in starts:
        result = parseSection(text, start, reportYear, lines, aliases, progress)
        if not any(issue.kind == "no_financial_rows" for issue in result.issues):
            return result
        first = first or result
    return first


def parseSection(text: list[tuple[int, str]], start: int, reportYear: int, lines: dict[str, Line], aliases: list[Alias], progress: Progress = None) -> Result:
    issues: list[Issue] = []
    evidence = []

    years: tuple[int, int] | None = None
    found = {}
    buffer: list[str] = []
    conflictingHeader = False
    unsupportedContext = False

    region = text[start + 1 :]
    stated = re.search(r'\byear\s+ended\s+(?:december\s+31,?\s*)?(20\d\d)\b', ' '.join(line for _, line in region[:12]), re.I)
    if stated and int(stated[1]) != reportYear:
        unsupportedContext = True
        issues.append(Issue('error', 'document_year_mismatch', '', f'The financial highlights refer to the year ended {stated[1]}, but this is the {reportYear} report. All rows are held back until a reviewer confirms which year the table describes.', text[start][0]))
    for index, (page, line) in enumerate(region):
        if END.search(line):
            break
        if unsupportedUnits(line):
            unsupportedContext = True
            issues.append(Issue("error", "unsupported_units", "", f"The table states amounts in another unit or scale (\"{line[:120]}\"). No conversion to pesos is assumed; the rows are held back.", page))
        if re.fullmatch(r"\s*(?:general fund|special education fund|trust fund)\s*", line, re.I):
            unsupportedContext = True
            issues.append(Issue("error", "unsupported_fund_scope", "", f"The table covers a single fund (\"{line}\"), not all funds combined. The rows are held back until a reviewer confirms their scope.", page))
        header = HEADER_YEARS.search(line)
        # Narrative dates are not column headings.
        headerText = re.sub(r"\b20\d\d\b|particulars?|accounts?|description|financial (?:condition|position|performance)|increase|decrease|amount|percent\w*|change|current|prior|year", "", line, flags=re.I)
        if header and not re.search(r"[a-z]", headerText, re.I):
            nextYears = (int(header.group(1)), int(header.group(2)))
            conflictingHeader = years is not None and sorted(nextYears) != sorted(years)
            if conflictingHeader:
                issues.append(Issue("error", "header_year_context_changed", "", f"A later heading on page {page} changes the column years from {years[0]} and {years[1]} to {nextYears[0]} and {nextYears[1]}. It cannot be determined from this report alone whether the heading or the amounts below it are wrong, so those rows are held back.", page))
            elif years is not None and years != nextYears:
                issues.append(Issue("warning", "column_order_changed", "", f"A later heading on page {page} lists the years in the opposite order ({nextYears[0]} then {nextYears[1]}). Each row is read in the order of the heading printed above it.", page))
            years = nextYears
            buffer = []
            continue
        row = ROW.match(line)
        following = []
        for _, after in region[index + 1 : index + 1 + LABEL_LINES]:
            if ROW.match(after) or END.search(after):
                break
            following.append(after)
        if not row:
            number = re.search(r"[0-9₱$€£]|P=?\s*[(−-]?[0-9]", line)
            if number and (hit := labelMatch(line[:number.start()].rstrip(" (−–—-"), buffer, following, lines, aliases)):
                issues.append(Issue("error", withheldKind(line), hit[0], heldBack(hit[0], line), page))
                evidence.append({"page": page, "line_code": hit[0], "label": hit[2], "raw": line[:1000], "status": "withheld"})
                buffer = []
                continue
            buffer = (buffer + [line])[-LABEL_LINES:]
            continue
        hit = labelMatch(row.group("label"), buffer, following, lines, aliases)
        if hit is None:
            number = re.search(r"[0-9₱$€£]|P=?\s*[(−-]?[0-9]", line)
            malformed = labelMatch(line[:number.start()].rstrip(" (−–—-"), buffer, following, lines, aliases) if number else None
            if malformed:
                issues.append(Issue("error", withheldKind(line), malformed[0], heldBack(malformed[0], line), page))
                evidence.append({"page": page, "line_code": malformed[0], "label": malformed[2], "raw": line[:1000], "status": "withheld"})
        buffer = []
        if hit is None:
            continue
        code, variant, label = hit
        # Santo Niño 2019 prints "Total Appropriations –" with its "Current" two lines down, below another row; the
        # hyphen in "Total Appropriations - 59,825,645.91" is label punctuation, not a not-reported dash cell.
        if variant == "unspecified_period" and (re.search(r"[-–—]\s*$", row.group("label")) or row.group("amounts").lstrip().startswith("-")):
            issues.append(Issue("error", "incomplete_row", code, f"{lineLabel(code)}: the words Current or Continuing are printed on a separate line from the amounts, so the row cannot be matched to its line automatically. The row is held back for a reviewer; the printed page may be correct.", page))
            evidence.append({"page": page, "line_code": code, "label": label, "raw": line[:1000], "status": "withheld"})
            continue
        prefix = re.sub(r"^(?:[a-z]|[0-9]+|[ivx]+)[.)]\s+", "", row.group("label"), flags=re.I)
        tokens = TOKEN.findall(row.group("amounts"))
        try:
            if re.search(r"[0-9₱$€£=]", prefix) or re.search(r"\(\s*(?:P=?)?\s*$|(?<![a-z])P=?\s*$|(?<![a-z])[-−]\s*$", prefix, re.I):
                # "Appropriations- Current" wraps its qualifier below; a hyphen joined to a word is not a minus sign.
                raise ValueError("Malformed monetary prefix")
            amounts = [parseAmount(token) for token in tokens]
            if len(amounts) < 2 or TOKEN.sub('', row.group('amounts')).strip():
                raise ValueError("Incomplete monetary cells")
        except ValueError:
            issues.append(Issue("error", withheldKind(line), code, heldBack(code, line), page))
            evidence.append({"page": page, "line_code": code, "label": label, "raw": line[:1000], "status": "withheld"})
            continue
        if len(label) > 300:
            issues.append(Issue('error', 'unreadable_label', code, f'{lineLabel(code)}: the row label is too long to record as evidence. The row is held back for a reviewer.', page))
            continue
        evidence.append({"page": page, "line_code": code, "label": label, "raw": line[:1000], "tokens": tokens,
                         "printed_years": list(years) if years else None, "status": "withheld" if conflictingHeader else "candidate"})
        if conflictingHeader:
            continue
        trailing = row.group("trailing").strip()
        if trailing and not (PERCENT.fullmatch(trailing) or re.fullmatch(r"\(?-?\d+(?:\.\d+)?\)?|%", trailing)):
            issues.append(Issue("warning", "unreadable_change", code, f"{lineLabel(code)}: the Increase (Decrease) or percentage column is printed as \"{trailing}\", which is not a valid figure. The amounts for both years were read normally.", page))
        if code in found:
            earlier = found[code]
            earlierYears = earlier[4] or (reportYear, reportYear - 1)
            rowYears = years or (reportYear, reportYear - 1)
            if (dict(zip(earlierYears, earlier[0][:2])), earlier[1]) != (dict(zip(rowYears, amounts[:2])), variant):
                issues.append(Issue("error", "conflicting_values", code, f"{lineLabel(code)} appears twice in the table with different amounts. Neither row is published until a reviewer decides which applies.", page))
            continue
        found[code] = (amounts, variant, label, page, years)

    figures: list[Figure] = []
    changes: dict[str, Decimal] = {}
    headerIssues = set()
    for code, (amounts, variant, label, page, rowYears) in found.items():
        if rowYears is None or rowYears[0] == rowYears[1]:
            kind = "header_years_missing" if rowYears is None else "header_years_repeated"
            if kind not in headerIssues:
                issues.append(Issue("error", kind, "", ("The two amount columns carry the same year heading. The years were assigned from the report year and must be confirmed by a reviewer." if kind == "header_years_repeated" else "The column year headings could not be read. The years were assigned from the report year and must be confirmed by a reviewer."), page))
                headerIssues.add(kind)
            rowYears = (reportYear, reportYear - 1)
        elif sorted(rowYears) != [reportYear - 1, reportYear] and rowYears not in headerIssues:
            issues.append(Issue("error", "header_year_mismatch", "", f"The columns are headed {rowYears[0]} and {rowYears[1]}, which does not match the {reportYear} report. It cannot be determined from this report alone whether the headings or the amounts are wrong; the amounts are not published until a reviewer confirms them.", page))
            headerIssues.add(rowYears)
        for year, value in zip(rowYears, amounts[:2]):
            if value is not None:
                figures.append(Figure(code, year, value, variant, label, page))
                preview(progress, figures[-1])
            else:
                issues.append(Issue("info", "value_not_reported", code, f"{lineLabel(code)} for {year} is printed as a dash. It is recorded as not reported, not as zero.", page, year))
        if len(amounts) > 2 and amounts[2] is not None:
            changes[code] = amounts[2]
    for code in CORE:
        if code not in found:
            issues.append(Issue("warning", "line_not_found", code, f"{lines[code].label} was not found in the Financial Highlights table. The report may not show it, or its row could not be read.", text[start][0]))
    if not found:
        issues.append(Issue('error', 'no_financial_rows', '', 'No amounts could be read from the Financial Highlights table. The amounts must be entered by a reviewer from the page.', text[start][0]))
    for item in evidence:
        item['raw'] = item['raw'][:500]
        item['label'] = item['label'][:300]
    return Result([] if unsupportedContext else figures, issues, changes, evidence[:80])


def heldBack(code: str, line: str) -> str:
    if hasMisprintedAmount(line):
        return (f"{lineLabel(code)}: the row is printed as \"{line[:160]}\". An amount in it has a misplaced comma or period, "
                "so it is not a valid peso amount. The row is held back; a reviewer must read the amount from the page.")
    return (f"{lineLabel(code)}: the PDF text splits this row across lines, so its amounts cannot be matched to their columns "
            "automatically. The row is held back for a reviewer; the printed page may be correct.")


def withheldKind(line: str) -> str:
    """A misprinted amount is the report's; a row whose cells wrap or split is the parser's limitation."""
    return "malformed_amount" if hasMisprintedAmount(line) else "incomplete_row"


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
