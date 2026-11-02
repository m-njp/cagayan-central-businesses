"""Command line: python -m coa extract. Run through `composer data:finances:extract`."""

import argparse
import csv
import hashlib
import logging
from decimal import Decimal
from collections import Counter, defaultdict
from pathlib import Path

from . import EXTRACTOR_VERSION
from .archives import addWorkbooks
from .checks import documentChecks, mislabelledYears, restatementChecks, swapYears
from .lines import load
from .summary import Issue, parseSummary
from .workbook import readAnnex, readFs

ROOT = Path(__file__).resolve().parents[3]
TOWNS = ROOT / "data" / "towns"
SUMMARIES = TOWNS / "executive-summaries"
WORKBOOKS = TOWNS / "financial-statements"
OUTPUT = TOWNS / "finances"
LOG_FILE = Path(__file__).resolve().parents[1] / "logs" / "extract.log"

EXTRACTED_COLUMNS = [
    "psgc_code", "town", "reported_in_year", "document_kind", "fiscal_year", "line_code", "basis", "value",
    "definition_variant", "source_label", "location", "document_sha256", "extractor_version",
]
ISSUE_COLUMNS = ["psgc_code", "town", "reported_in_year", "severity", "kind", "line_code", "message", "location"]
DOCUMENT_COLUMNS = ["psgc_code", "town", "fiscal_year", "kind", "availability", "file", "source_url", "sha256", "checked_on"]

log = logging.getLogger("coa")


def main() -> None:
    parser = argparse.ArgumentParser(prog="coa")
    commands = parser.add_subparsers(dest="command", required=True)
    commands.add_parser("extract", help="Read every Executive Summary and workbook into data/towns/finances/")
    adding = commands.add_parser("add-workbooks", help="Copy statement workbooks out of downloaded Annual Audit Report ZIPs")
    adding.add_argument("listing", type=Path, help="CSV with archive,source_url,retrieved_on")
    arguments = parser.parse_args()
    if arguments.command == "add-workbooks":
        for message in addWorkbooks(arguments.listing.resolve(), SUMMARIES, WORKBOOKS):
            print(message)
        return
    configureLogging()
    extract()


def configureLogging() -> None:
    """Everything goes to tools/coa/logs/extract.log, rewritten each run; warnings and errors also print."""
    LOG_FILE.parent.mkdir(exist_ok=True)
    fileHandler = logging.FileHandler(LOG_FILE, mode="w", encoding="utf-8")
    fileHandler.setFormatter(logging.Formatter("%(asctime)s %(levelname)s %(message)s"))
    consoleHandler = logging.StreamHandler()
    consoleHandler.setLevel(logging.WARNING)
    consoleHandler.setFormatter(logging.Formatter("%(levelname)s %(message)s"))
    log.setLevel(logging.DEBUG)
    log.handlers = [fileHandler, consoleHandler]
    logging.getLogger("pdfminer").setLevel(logging.ERROR)


def extract() -> None:
    log.info("Extractor version %s", EXTRACTOR_VERSION)
    lines, aliases = load(OUTPUT / "lines.csv")
    sources = readCsv(SUMMARIES / "sources.csv")
    extracted: dict[int, list[dict]] = defaultdict(list)
    issues: dict[int, list[dict]] = defaultdict(list)
    byTown: dict[str, dict[int, list]] = defaultdict(dict)

    for source in sorted(sources, key=lambda row: (int(row["year"]), row["town"])):
        year, path = int(source["year"]), SUMMARIES / source["file"]
        documentIssues: list[Issue] = []
        try:
            if hashlib.sha256(path.read_bytes()).hexdigest() != source["sha256"]:
                documentIssues.append(Issue("error", "checksum_mismatch", "", "File differs from sources.csv"))
            result = parseSummary(path, year, lines, aliases)
            if mislabelledYears(result.figures, result.changes, byTown[source["psgc_code"]].get(year - 1, []), year):
                result.figures = swapYears(result.figures, year)
                documentIssues.append(Issue("warning", "header_years_swapped", "", f"Columns are headed {year} then {year - 1} but printed {year - 1} then {year}; read in printed order"))
            documentIssues += result.issues + documentChecks(result.figures, result.changes, year)
        except Exception as error:
            # One unreadable file must not stop the run; its figures are simply absent and the issue says why.
            log.exception("%s %s: parser failed", year, source["town"])
            issues[year].append(issueRow(source, year, Issue("error", "parser_failed", "", f"{type(error).__name__}: {error}; see tools/coa/logs/extract.log")))
            continue
        byTown[source["psgc_code"]][year] = result.figures
        storedFigures = stored(result.figures, lines)
        for figure in storedFigures:
            extracted[year].append({
                "psgc_code": source["psgc_code"], "town": source["town"], "reported_in_year": year,
                "document_kind": "executive_summary", "fiscal_year": figure.fiscalYear, "line_code": figure.code, "basis": "actual",
                "value": f"{figure.value:.2f}", "definition_variant": figure.variant or "",
                "source_label": figure.sourceLabel, "location": f"page {figure.page}",
                "document_sha256": source["sha256"], "extractor_version": EXTRACTOR_VERSION,
            })
        issues[year] += [issueRow(source, year, issue) for issue in documentIssues]
        logDocument(f"{year} {source['town']} executive summary", len(storedFigures), documentIssues)

    for code, reports in byTown.items():
        source = next(row for row in sources if row["psgc_code"] == code)
        for year, issue in restatementChecks(reports):
            issues[year].append(issueRow(source, year, issue))

    workbooks = readCsv(WORKBOOKS / "sources.csv")
    extractWorkbooks(workbooks, lines, aliases, extracted, issues)

    (OUTPUT / "extracted").mkdir(parents=True, exist_ok=True)
    (OUTPUT / "issues").mkdir(parents=True, exist_ok=True)
    for year in sorted(extracted.keys() | issues.keys()):
        writeCsv(OUTPUT / "extracted" / f"{year}.csv", EXTRACTED_COLUMNS, extracted[year])
        writeCsv(OUTPUT / "issues" / f"{year}.csv", ISSUE_COLUMNS, sorted(issues[year], key=lambda row: (row["town"], row["severity"], row["kind"], row["line_code"])))
    writeCsv(OUTPUT / "documents.csv", DOCUMENT_COLUMNS, documents(sources) + workbookDocuments(workbooks))
    report(extracted, issues, len(sources))


def extractWorkbooks(workbooks: list[dict], lines, aliases, extracted: dict[int, list[dict]], issues: dict[int, list[dict]]) -> None:
    """Breakdowns from the statement workbooks, checked against the same town's Executive Summary totals."""
    summary = {
        (row["psgc_code"], int(row["fiscal_year"]), row["line_code"]): Decimal(row["value"])
        for yearRows in extracted.values() for row in yearRows
        if row["document_kind"] == "executive_summary" and row["reported_in_year"] == int(row["fiscal_year"])
    }
    readers = {"fs_workbook": readFs, "annex_workbook": readAnnex}
    byTown: dict[tuple[str, str], dict[int, list]] = defaultdict(dict)
    for source in sorted(workbooks, key=lambda row: (int(row["year"]), row["town"], row["kind"])):
        year, path = int(source["year"]), WORKBOOKS / source["file"]
        try:
            result = readers[source["kind"]](path, year, lines, aliases)
            if hashlib.sha256(path.read_bytes()).hexdigest() != source["sha256"]:
                result.issues.append(Issue("error", "checksum_mismatch", "", f"{source['file']} differs from sources.csv"))
            if source["kind"] == "fs_workbook":
                result.issues += summaryChecks(result.figures, summary, source["psgc_code"], year)
        except Exception as error:
            log.exception("%s %s %s: parser failed", year, source["town"], source["kind"])
            issues[year].append(issueRow(source, year, Issue("error", "parser_failed", "", f"{source['file']}: {type(error).__name__}: {error}; see tools/coa/logs/extract.log")))
            continue
        byTown[(source["psgc_code"], source["kind"])][year] = result.figures
        storedFigures = stored(result.figures, lines)
        for figure in storedFigures:
            extracted[year].append({
                "psgc_code": source["psgc_code"], "town": source["town"], "reported_in_year": year,
                "document_kind": source["kind"], "fiscal_year": figure.fiscalYear, "line_code": figure.code,
                "basis": figure.basis, "value": f"{figure.value:.2f}", "definition_variant": figure.variant or "",
                "source_label": figure.sourceLabel, "location": figure.location,
                "document_sha256": source["sha256"], "extractor_version": EXTRACTOR_VERSION,
            })
        issues[year] += [issueRow(source, year, issue) for issue in result.issues]
        logDocument(f"{year} {source['town']} {source['kind']}", len(storedFigures), result.issues)
    for (code, _), reports in byTown.items():
        source = next(row for row in workbooks if row["psgc_code"] == code)
        for year, issue in restatementChecks(reports):
            issues[year].append(issueRow(source, year, issue))


def logDocument(name: str, figureCount: int, documentIssues: list[Issue]) -> None:
    log.info("%s: %d figures stored", name, figureCount)
    for issue in documentIssues:
        level = {"error": logging.ERROR, "warning": logging.WARNING}.get(issue.severity, logging.INFO)
        log.log(level, "%s: %s %s %s", name, issue.kind, issue.code, issue.message)


def stored(figures, lines) -> list:
    """Lines in the "checks" group are read for the arithmetic and section boundaries, never written."""
    return [figure for figure in figures if lines[figure.code].group != "checks"]


def summaryChecks(figures, summary: dict, psgc: str, year: int) -> list[Issue]:
    """Revenue sources should add up to the summary's revenue, and expense types to its expenses."""
    found = []
    for total, prefix in (("performance.revenue", "revenue."), ("performance.expenses", "expense.")):
        parts = [figure.value for figure in figures if figure.fiscalYear == year and figure.basis == "actual" and figure.code.startswith(prefix)]
        stated = summary.get((psgc, year, total))
        if parts and stated is not None and abs(sum(parts) - stated) > Decimal("1.00"):
            found.append(Issue("warning", "differs_from_summary", total, f"{year}: workbook lines add up to {sum(parts)}, the Executive Summary states {stated}"))
    return found


def workbookDocuments(workbooks: list[dict]) -> list[dict]:
    rows = [{
        "psgc_code": row["psgc_code"], "town": row["town"], "fiscal_year": row["year"], "kind": row["kind"],
        "availability": "available", "file": f"financial-statements/{row['file']}", "source_url": row["source_url"],
        "sha256": row["sha256"], "checked_on": row["retrieved_on"],
    } for row in workbooks]
    for row in readCsv(WORKBOOKS / "not-published.csv"):
        for kind in ("fs_workbook", "annex_workbook"):
            rows.append({
                "psgc_code": row["psgc_code"], "town": row["town"], "fiscal_year": row["year"], "kind": kind,
                "availability": "not_published", "file": "", "source_url": "", "sha256": "", "checked_on": row["checked_on"],
            })
    return sorted(rows, key=lambda row: (int(row["fiscal_year"]), row["kind"], row["town"]))


def documents(sources: list[dict]) -> list[dict]:
    """Every town and year in the collected range, including years COA has not published."""
    towns = {row["psgc_code"]: row["town"] for row in sources}
    years = sorted({int(row["year"]) for row in sources})
    held = {(row["psgc_code"], int(row["year"])): row for row in sources}
    checked = {int(row["year"]): max(r["retrieved_on"] for r in sources if r["year"] == row["year"]) for row in sources}
    rows = []
    for year in years:
        for code, town in sorted(towns.items(), key=lambda item: item[1]):
            source = held.get((code, year))
            rows.append({
                "psgc_code": code, "town": town, "fiscal_year": year, "kind": "executive_summary",
                "availability": "available" if source else "not_published",
                "file": f"executive-summaries/{source['file']}" if source else "",
                "source_url": source["source_url"] if source else "",
                "sha256": source["sha256"] if source else "",
                "checked_on": source["retrieved_on"] if source else checked[year],
            })
    return rows


def issueRow(source: dict, year: int, issue: Issue) -> dict:
    return {
        "psgc_code": source["psgc_code"], "town": source["town"], "reported_in_year": year,
        "severity": issue.severity, "kind": issue.kind, "line_code": issue.code, "message": issue.message,
        "location": f"page {issue.page}" if issue.page else "",
    }


def report(extracted: dict[int, list[dict]], issues: dict[int, list[dict]], documentsRead: int) -> None:
    rows = [row for yearRows in extracted.values() for row in yearRows]
    current = {(row["psgc_code"], row["reported_in_year"], row["line_code"]) for row in rows if row["fiscal_year"] == row["reported_in_year"]}
    print(f"Documents read: {documentsRead}")
    print(f"Figures extracted: {len(rows)}")
    for code in ("performance.revenue", "performance.expenses", "budget.appropriations_current", "budget.obligations_current"):
        print(f"  {code}: {sum(1 for key in current if key[2] == code)} of {documentsRead} reports")
    severities = Counter(row["severity"] for yearRows in issues.values() for row in yearRows)
    kinds = Counter((row["severity"], row["kind"]) for yearRows in issues.values() for row in yearRows)
    print(f"Issues: {dict(severities)}")
    for (severity, kind), count in sorted(kinds.items()):
        print(f"  {severity} {kind}: {count}")
    print(f"Log: {LOG_FILE.relative_to(ROOT).as_posix()}")


def readCsv(path: Path) -> list[dict]:
    with path.open(encoding="utf-8", newline="") as handle:
        return list(csv.DictReader(handle))


def writeCsv(path: Path, columns: list[str], rows: list[dict]) -> None:
    with path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=columns, lineterminator="\n")
        writer.writeheader()
        writer.writerows(rows)


if __name__ == "__main__":
    main()
