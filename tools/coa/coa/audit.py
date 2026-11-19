"""Stage a COA extraction and compare evidence without importing or approving figures."""

import argparse
import csv
import hashlib
import json
import re
from collections import Counter, defaultdict
from datetime import datetime, timezone
from pathlib import Path
from uuid import uuid4

from . import __main__ as batch

ROOT = batch.ROOT
CANONICAL = batch.OUTPUT
SOURCES = batch.SUMMARIES / "sources.csv"
KEY_FIELDS = ("psgc_code", "reported_in_year", "document_kind", "fiscal_year", "line_code", "basis")
SOURCE_ARITHMETIC = {"balance_mismatch", "net_income_mismatch", "revenue_sources_mismatch", "change_mismatch"}
HARD_WARNINGS = {"digits_unconfirmed", "engine_failed", "header_years_missing", "header_years_repeated",
                 "found_after_section_end", "line_not_found"}
REVIEW_COLUMNS = ["psgc_code", "town", "reported_in_year", "document_kind", "document_sha256", "fiscal_year",
                  "severity", "kind", "line_code", "location", "action", "message"]


def digest(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def snapshotCsv(folder: Path) -> dict[str, str]:
    return {path.relative_to(folder).as_posix(): digest(path) for path in sorted(folder.rglob("*.csv"))}


def readRows(path: Path, required: tuple[str, ...] = ()) -> list[dict[str, str]]:
    with path.open(encoding="utf-8-sig", newline="") as source:
        reader = csv.DictReader(source)
        if not set(required) <= set(reader.fieldnames or []):
            raise ValueError(f"Missing required CSV fields: {path.name}")
        rows = list(reader)
    if any(None in row or any(value is None for value in row.values()) for row in rows):
        raise ValueError(f"Incomplete CSV record: {path.name}")
    return rows


def observations(folder: Path) -> dict[tuple[str, ...], dict[str, str]]:
    found = {}
    for path in sorted((folder / "extracted").glob("*.csv")):
        for row in readRows(path, KEY_FIELDS):
            key = tuple(row[field] for field in KEY_FIELDS)
            if key in found:
                raise ValueError(f"Duplicate observation key: {key}")
            found[key] = row
    return found


def compareObservations(before: dict, after: dict) -> dict:
    added = [after[key] for key in sorted(after.keys() - before.keys())]
    removed = [before[key] for key in sorted(before.keys() - after.keys())]
    changed = []
    fields = Counter()
    for key in sorted(before.keys() & after.keys()):
        differences = {field: {"before": before[key].get(field), "after": after[key].get(field)}
                       for field in sorted(before[key].keys() | after[key].keys())
                       if before[key].get(field) != after[key].get(field)}
        if differences:
            changed.append({"key": dict(zip(KEY_FIELDS, key)), "fields": differences})
            fields.update(differences.keys())
    return {"counts": {"baseline": len(before), "staged": len(after), "added": len(added), "removed": len(removed),
                       "changed": len(changed), "valueChanges": fields["value"],
                       "definitionChanges": fields["definition_variant"], "locationChanges": fields["location"],
                       "documentHashChanges": fields["document_sha256"]},
            "fieldChanges": dict(sorted(fields.items())), "added": added, "removed": removed, "changed": changed}


def reviewAction(issue: dict[str, str]) -> str:
    if issue["severity"] == "info":
        return "informational"
    if issue["kind"] in SOURCE_ARITHMETIC:
        return "inspect_source_arithmetic"
    if issue["severity"] == "error" or issue["kind"] in HARD_WARNINGS:
        return "resolve_extraction_evidence"
    return "inspect_warning"


def verifyManifest(output: Path) -> dict:
    manifest = json.loads((output / "run.json").read_text(encoding="utf-8"))
    files = manifest.get("files")
    if not isinstance(files, dict) or any(not isinstance(name, str) for name in files) or manifest.get("canonical_updated") is not False:
        raise ValueError("Extraction did not produce a staged file manifest")
    actual = {path.relative_to(output).as_posix() for path in output.rglob("*.csv") if path != output / 'review-items.csv'}
    if actual != set(files):
        raise ValueError("Staged CSV inventory differs from its extraction manifest")
    for name, expected in files.items():
        relative = Path(name)
        path = (output / relative).resolve()
        if relative.is_absolute() or ".." in relative.parts or not path.is_relative_to(output.resolve()) or not path.is_file():
            raise ValueError("Invalid staged file manifest path")
        if not isinstance(expected, str) or re.fullmatch(r"[a-f0-9]{64}", expected) is None or digest(path) != expected:
            raise ValueError(f"Staged file checksum differs: {name}")
    return manifest


def summaryCoverage(sources: list[dict[str, str]], staged: dict, issues: list[dict[str, str]]) -> dict:
    rows = []
    byReport = defaultdict(list)
    for issue in issues:
        byReport[(issue.get('psgc_code'), issue.get('reported_in_year'))].append(issue)
    for source in sorted(sources, key=lambda row: (row["year"], row["psgc_code"])):
        required = [(line, str(year)) for line in ("performance.revenue", "performance.expenses")
                    for year in (int(source["year"]), int(source["year"]) - 1)]
        missing, blocked = [], []
        for line, year in required:
            key = (source["psgc_code"], source["year"], "executive_summary", year, line, "actual")
            if key not in staged:
                missing.append({"lineCode": line, "fiscalYear": int(year)})
                continue
            if staged[key].get('document_sha256') != source['sha256']:
                blocked.append({"lineCode": line, "fiscalYear": int(year)})
                continue
            for issue in byReport[(source['psgc_code'], source['year'])]:
                if issue.get("document_kind", "executive_summary") not in ("", "executive_summary"):
                    continue
                if issue.get("line_code", "") not in ("", line) or issue.get("fiscal_year", "") not in ("", year):
                    continue
                if issue.get("document_sha256", source["sha256"]) not in ("", source["sha256"]) or reviewAction(issue) == "resolve_extraction_evidence":
                    blocked.append({"lineCode": line, "fiscalYear": int(year)})
                    break
        rows.append({"psgcCode": source["psgc_code"], "town": source["town"], "reportYear": int(source["year"]),
                     "documentSha256": source["sha256"], "requiredCells": 4, "candidateCells": 4 - len(missing),
                     "evidenceClearCells": 4 - len(missing) - len(blocked), "missingCells": missing, "blockedCells": blocked})
    return {"meaning": "Evidence-clear cells have no blocking extraction finding; this is not financial sign-off.",
            "reports": len(rows), "requiredCells": len(rows) * 4,
            "candidateCells": sum(row["candidateCells"] for row in rows),
            "evidenceClearCells": sum(row["evidenceClearCells"] for row in rows), "rows": rows}


def runAudit(output: Path | None = None) -> dict:
    if output is None:
        name = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ") + "-audit-" + uuid4().hex[:8]
        output = ROOT / "storage/app/source-review/coa/parser-runs" / name
    elif not output.is_absolute():
        output = ROOT / output
    output = output.resolve()
    if output.is_relative_to(CANONICAL.resolve()) or any(output.is_relative_to(path.resolve()) for path in (SOURCES.parent, batch.WORKBOOKS)):
        raise ValueError("Audit output must stay outside canonical source and finance folders")
    if output.exists() and (not output.is_dir() or any(output.iterdir())):
        raise ValueError("Audit output already contains files; choose a new directory")
    beforeCsv = snapshotCsv(CANONICAL)
    sourceHash = digest(SOURCES)
    before = observations(CANONICAL)
    sources = readRows(SOURCES, ("psgc_code", "town", "year", "sha256"))
    output.mkdir(parents=True, exist_ok=True)
    marker = output / ".audit-running"
    with marker.open("x", encoding="utf-8") as handle:
        handle.write("This directory belongs to an unfinished audit.\n")
    try:
        if batch.extract(output) is False:
            raise RuntimeError('Staged extraction did not complete')
    finally:
        if snapshotCsv(CANONICAL) != beforeCsv or digest(SOURCES) != sourceHash:
            raise RuntimeError("Canonical CSVs changed during the audit; results require reconciliation")
    manifest = verifyManifest(output)
    staged = observations(output)
    issues = [row for path in sorted((output / "issues").glob("*.csv"))
              for row in readRows(path, ("psgc_code", "reported_in_year", "severity", "kind", "line_code", "message", "location"))]
    if any(issue["severity"] not in ("info", "warning", "error") for issue in issues):
        raise ValueError("Unsupported diagnostic severity")
    reviewRows = [{field: row.get(field, "") for field in REVIEW_COLUMNS} | {"action": reviewAction(row)}
                  for row in sorted(issues, key=lambda row: tuple(row.get(field, "") for field in REVIEW_COLUMNS))]
    batch.writeCsv(output / "review-items.csv", REVIEW_COLUMNS, reviewRows)
    comparison = compareObservations(before, staged)
    coverage = summaryCoverage(sources, staged, issues)
    needsReview = (manifest.get('status') == 'needs_review' or any(row["severity"] in ("warning", "error") for row in issues)
                   or coverage["evidenceClearCells"] != coverage["requiredCells"]
                   or any(comparison['counts'][field] for field in ('added', 'removed', 'changed')))
    result = {"parserVersion": manifest.get("parser_version"), "status": "needs_review" if needsReview else "completed",
              "meaning": "Audit completed; figures are candidates for independent verification, not approved financial data.",
              "output": str(output), "canonicalUnchanged": True, "sourceRegisterSha256": sourceHash,
              "baselineCsvSha256": beforeCsv, "comparison": comparison, "coverage": coverage,
              "issueCounts": dict(sorted(Counter(row["severity"] for row in issues).items())),
              "reviewActions": dict(sorted(Counter(row["action"] for row in reviewRows).items()))}
    (output / "comparison.json").write_text(json.dumps(result, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    auditFiles = {path.relative_to(output).as_posix(): digest(path) for path in sorted(output.rglob('*'))
                  if path.is_file() and path != marker}
    (output / 'audit-manifest.json').write_text(json.dumps({'status': result['status'], 'files': auditFiles}, indent=2) + '\n', encoding='utf-8')
    marker.unlink()
    return result


def main(argv: list[str] | None = None) -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", type=Path, help="New staging folder, relative to the repository root")
    args = parser.parse_args(argv)
    batch.configureLogging()
    try:
        result = runAudit(args.output)
    except (OSError, ValueError, RuntimeError) as error:
        parser.exit(2, f"Audit failed: {error}\n")
    print(json.dumps({key: result[key] for key in ("status", "output", "canonicalUnchanged", "issueCounts", "reviewActions")} | {
        "observationCounts": result["comparison"]["counts"],
        "requiredSummaryCells": result["coverage"]["requiredCells"],
        "candidateSummaryCells": result["coverage"]["candidateCells"],
        "evidenceClearSummaryCells": result["coverage"]["evidenceClearCells"],
    }))


if __name__ == "__main__":
    main()
