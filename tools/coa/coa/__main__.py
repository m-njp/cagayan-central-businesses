"""Command line: python -m coa extract. Run through `composer data:finances:extract`."""

import argparse
import csv
import hashlib
import logging
import json
import time
from decimal import Decimal
from collections import Counter, defaultdict
from pathlib import Path

from . import EXTRACTOR_VERSION
from .archives import addWorkbooks
from .checks import documentChecks, mislabelledYears, restatementChecks, staleHeaderFigures, swapYears
from .lines import load
from .numbers import peso
from .summary import Issue, family, parseSummary
from .workbook import readAnnex, readFs

ROOT = Path(__file__).resolve().parents[3]
TOWNS = ROOT / "data" / "towns"
SUMMARIES = TOWNS / "executive-summaries"
WORKBOOKS = TOWNS / "financial-statements"
OUTPUT = TOWNS / "finances"
LOG_FILE = Path(__file__).resolve().parents[1] / "logs" / "extract.log"

EXTRACTED_COLUMNS = [
    "psgc_code", "town", "reported_in_year", "document_kind", "fiscal_year", "line_code", "basis", "value",
    "definition_variant", "source_label", "location", "document_sha256", "extractor_version", "input_values",
]
ISSUE_COLUMNS = ["psgc_code", "town", "reported_in_year", "severity", "kind", "family", "line_code", "message", "location", "document_kind", "document_sha256", "fiscal_year"]
DOCUMENT_COLUMNS = ["psgc_code", "town", "fiscal_year", "kind", "availability", "file", "source_url", "sha256", "checked_on"]

log = logging.getLogger("coa")


def main() -> None:
    parser = argparse.ArgumentParser(prog="coa")
    commands = parser.add_subparsers(dest="command", required=True)
    extracting = commands.add_parser("extract", help="Read every Executive Summary and workbook into data/towns/finances/")
    extracting.add_argument('--output', type=Path, help='Stage a run outside canonical finance CSVs for comparison')
    adding = commands.add_parser("add-workbooks", help="Copy statement workbooks out of downloaded Annual Audit Report ZIPs")
    adding.add_argument("listing", type=Path, help="CSV with archive,source_url,retrieved_on")
    arguments = parser.parse_args()
    if arguments.command == "add-workbooks":
        for message in addWorkbooks(arguments.listing.resolve(), SUMMARIES, WORKBOOKS):
            print(message)
        return
    configureLogging()
    output = arguments.output
    if output is not None and not output.is_absolute():
        output = ROOT / output
    if not extract(output):
        raise SystemExit(2)


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


def extract(output: Path | None = None) -> bool:
    output = (output or OUTPUT).resolve()
    log.info("Extractor version %s", EXTRACTOR_VERSION)
    lines, aliases = load(OUTPUT / "lines.csv")
    sources = readCsv(SUMMARIES / "sources.csv")
    extracted: dict[int, list[dict]] = defaultdict(list)
    issues: dict[int, list[dict]] = defaultdict(list)
    byTown: dict[str, dict[int, list]] = defaultdict(dict)
    evidence = {}

    for source in sorted(sources, key=lambda row: (int(row["year"]), row["town"])):
        year, path = int(source["year"]), SUMMARIES / source["file"]
        documentIssues: list[Issue] = []
        try:
            if hashlib.sha256(path.read_bytes()).hexdigest() != source["sha256"]:
                issues[year].append(issueRow(source, year, Issue("error", "checksum_mismatch", "", "File differs from sources.csv; no figures emitted")))
                continue
            result = parseSummary(path, year, lines, aliases, expectedTown=source['town'], expectedProvince='Cagayan')
            if hashlib.sha256(path.read_bytes()).hexdigest() != source['sha256']:
                issues[year].append(issueRow(source, year, Issue('error', 'checksum_mismatch', '', 'Source changed during extraction; no figures emitted')))
                continue
            evidence[f"{year}/{source['psgc_code']}.json"] = {
                'source_sha256': source['sha256'], 'parser_version': EXTRACTOR_VERSION, 'rows': result.evidence,
            }
            if mislabelledYears(result.figures, result.changes, byTown[source["psgc_code"]].get(year - 1, []), year):
                result.figures = swapYears(result.figures, year)
                documentIssues.append(Issue("warning", "header_years_swapped", "", f"The columns are headed {year} and {year - 1}, but the {year - 1} report and the Increase (Decrease) column show the amounts are printed in the order {year - 1} and {year}. The amounts were assigned to the years those reports confirm."))
            if restored := staleHeaderFigures(result.evidence, byTown[source["psgc_code"]].get(year - 1, []), year, lines, aliases):
                result.figures += restored
                result.issues = [issue for issue in result.issues if issue.kind != "header_year_context_changed"]
                documentIssues.append(Issue("warning", "header_years_stale", "", f"Some rows are headed {year - 1} and {year - 2}, which does not match the {year} report. The {year - 1} report shows the same amounts for {year - 1} in the second column, so the amounts were assigned to {year} and {year - 1}. Confirm against the page."))
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
                "input_values": json.dumps(getattr(figure, "inputs", []), ensure_ascii=False),
            })
        issues[year] += [issueRow(source, year, issue) for issue in documentIssues]
        logDocument(f"{year} {source['town']} executive summary", len(storedFigures), documentIssues)

    for code, reports in byTown.items():
        for year, issue in restatementChecks(reports):
            source = next(row for row in sources if row["psgc_code"] == code and int(row['year']) == year)
            issues[year].append(issueRow(source, year, issue))

    workbooks = readCsv(WORKBOOKS / "sources.csv")
    extractWorkbooks(workbooks, lines, aliases, extracted, issues)

    # The committed CSVs are the one source of truth: source errors stay visible in issues/ and Pending until signed off.
    canonical = output == OUTPUT.resolve()
    unresolved = any(row['severity'] == 'error' and row['family'] == 'reading' for rows in issues.values() for row in rows)
    needsReview = any(row['severity'] in ('error', 'warning') for rows in issues.values() for row in rows)
    from .audit import compareObservations, observations
    before = observations(output) if (output / "extracted").is_dir() else {}
    (output / "extracted").mkdir(parents=True, exist_ok=True)
    (output / "issues").mkdir(parents=True, exist_ok=True)
    for year in sorted(extracted.keys() | issues.keys()):
        writeCsv(output / "extracted" / f"{year}.csv", EXTRACTED_COLUMNS, extracted[year])
        writeCsv(output / "issues" / f"{year}.csv", ISSUE_COLUMNS, sorted(issues[year], key=lambda row: (row["town"], row["severity"], row["kind"], row["line_code"])))
    writeCsv(output / "documents.csv", DOCUMENT_COLUMNS, documents(sources) + workbookDocuments(workbooks))
    report(extracted, issues, len(sources))
    reportChanges(compareObservations(before, observations(output)))
    if canonical:
        return True
    # A staged run (--output, coa.audit) also keeps per-row evidence and a file manifest.
    for name, rows in evidence.items():
        path = output / 'evidence' / name
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(json.dumps(rows, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    manifest = {path.relative_to(output).as_posix(): hashlib.sha256(path.read_bytes()).hexdigest()
                for path in sorted(output.rglob('*.csv'))}
    (output / 'run.json').write_text(json.dumps({
        'parser_version': EXTRACTOR_VERSION, 'status': 'needs_review' if needsReview else 'completed',
        'unresolved_extraction_errors': unresolved,
        'canonical_updated': False, 'summary_documents': len(sources),
        'source_register_sha256': hashlib.sha256((SUMMARIES / 'sources.csv').read_bytes()).hexdigest() if (SUMMARIES / 'sources.csv').is_file() else None,
        'figures': sum(len(rows) for rows in extracted.values()),
        'issues': dict(Counter(row['severity'] for rows in issues.values() for row in rows)), 'files': manifest,
    }, indent=2) + '\n', encoding='utf-8')
    return True


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
            if hashlib.sha256(path.read_bytes()).hexdigest() != source["sha256"]:
                issues[year].append(issueRow(source, year, Issue("error", "checksum_mismatch", "", f"{source['file']} differs from sources.csv; no figures emitted")))
                continue
            result = readers[source["kind"]](path, year, lines, aliases, expectedTown=source['town'], expectedProvince='Cagayan')
            if hashlib.sha256(path.read_bytes()).hexdigest() != source['sha256']:
                issues[year].append(issueRow(source, year, Issue('error', 'checksum_mismatch', '', f"{source['file']} changed during extraction; no figures emitted")))
                continue
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
                "input_values": json.dumps(getattr(figure, "inputs", []), ensure_ascii=False),
            })
        issues[year] += [issueRow(source, year, issue) for issue in result.issues]
        logDocument(f"{year} {source['town']} {source['kind']}", len(storedFigures), result.issues)
    for (code, kind), reports in byTown.items():
        for year, issue in restatementChecks(reports):
            source = next(row for row in workbooks if row["psgc_code"] == code and row['kind'] == kind and int(row['year']) == year)
            issues[year].append(issueRow(source, year, issue))


def reportChanges(comparison: dict) -> None:
    """What this run changed against the files it replaced, so a reviewer reads the diff before importing."""
    counts = comparison["counts"]
    messages = [f"Compared with the previous files: {counts['added']} added, {counts['removed']} removed, {counts['valueChanges']} values changed"]
    byDocument = Counter()
    for change, rows in (("added", comparison["added"]), ("removed", comparison["removed"])):
        byDocument.update((row["reported_in_year"], row["town"], row["document_kind"], change) for row in rows)
    for (year, town, kind, change), count in sorted(byDocument.items()):
        messages.append(f"  {year} {town} {kind}: {count} {change}")
    for item in comparison["changed"]:
        if "value" in item["fields"]:
            key, value = item["key"], item["fields"]["value"]
            messages.append(f"  {key['reported_in_year']} {key['psgc_code']} {key['document_kind']} {key['fiscal_year']} {key['line_code']}: {value['before']} -> {value['after']}")
    for message in messages:
        print(message)
        log.info(message)


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
            found.append(Issue("warning", "differs_from_summary", total, f"{year}: the workbook lines add up to {peso(sum(parts))}, but the Executive Summary shows {peso(stated)}. The two documents may define the total differently."))
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
        "severity": issue.severity, "kind": issue.kind, "family": family(issue.kind), "line_code": issue.code, "message": issue.message,
        "location": f"page {issue.page}" if issue.page else "",
        "document_kind": source.get('kind', 'executive_summary'), "document_sha256": source['sha256'],
        "fiscal_year": issue.fiscalYear if issue.fiscalYear is not None else '',
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
    temporary = path.with_suffix('.tmp')
    with temporary.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=columns, lineterminator="\n")
        writer.writeheader()
        writer.writerows(rows)
    for attempt in range(6):
        try:
            temporary.replace(path)
            return
        except PermissionError:
            if attempt == 5:
                raise
            time.sleep(0.05 * (attempt + 1))


if __name__ == "__main__":
    main()
