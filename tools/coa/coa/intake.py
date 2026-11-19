"""Read one uploaded report. Default output is JSON; --events adds live JSON-line progress."""

import argparse
import hashlib
import json
import logging
import sys
import time
from dataclasses import asdict
from pathlib import Path
from typing import Any

from . import EXTRACTOR_VERSION
from .checks import documentChecks
from .confidence import confidenceScore
from .files import fileType
from .lines import Line, load
from .progress import Progress, report
from .summary import family, parseSummary

PACE_BUDGET_SECONDS = 20  # total pacing per run, well inside the queue job timeout


def serializeFigure(figure: Any) -> dict[str, Any]:
    return {"line_code": figure.code, "fiscal_year": figure.fiscalYear, "basis": "actual", "value": f"{figure.value:.2f}",
        "source_label": figure.sourceLabel, "location": f"page {figure.page}",
        "definition_variant": figure.variant, "inputs": list(getattr(figure, "inputs", [])),
        "document_kind": "executive_summary"}


def scan(path: Path, year: int, progress: Progress = None, expectedTown: str | None = None, expectedProvince: str | None = None) -> dict[str, Any]:
    """Read one Executive Summary PDF. Workbooks and archives are kept as sources, never read (D49)."""
    if fileType(path) != "pdf":
        raise ValueError("Only an Executive Summary PDF can be read; workbooks stay in the source library")
    sourceHash = hashlib.sha256(path.read_bytes()).hexdigest()
    lines, aliases = load(Path(__file__).resolve().parents[3] / "data/towns/finances/lines.csv")

    def documentProgress(event: dict[str, Any]) -> None:
        if event["type"] == "figure" and not isDisplayed(event["figure"]["line_code"], lines):
            return
        report(progress, event["type"], **{key: value for key, value in event.items() if key != "type"}, document_kind="executive_summary")

    report(progress, "stage", stage="scanning", message="Reading executive summary.")
    result = parseSummary(path, year, lines, aliases, documentProgress if progress else None, expectedTown, expectedProvince)
    result.issues += documentChecks(result.figures, result.changes, year)
    evidence = [{**item, "document_kind": "executive_summary", "document_sha256": sourceHash} for item in result.evidence]
    if not result.figures and sum(issue.kind == "engine_failed" for issue in result.issues) == 2:
        raise ValueError("Neither PDF reader could open this report")
    issues = []
    for issue in result.issues:
        serialized = {**asdict(issue), "family": family(issue.kind), "document_kind": "executive_summary", "document_sha256": sourceHash}
        issues.append(serialized)
        report(progress, "issue", issue=serialized, document_kind="executive_summary")
    figures = [serializeFigure(figure) for figure in result.figures if isDisplayed(figure.code, lines)]
    report(progress, "replace", document_kind="executive_summary", figures=figures)
    if hashlib.sha256(path.read_bytes()).hexdigest() != sourceHash:
        raise ValueError("The input source changed during extraction; no final result is safe to save")
    return {"parser_version": EXTRACTOR_VERSION, "source_sha256": sourceHash, "figures": figures, "issues": issues,
            "evidence": evidence, "confidence": confidenceScore(figures, issues)}


def isDisplayed(code: str, lines: dict[str, Line]) -> bool:
    line = lines[code]
    return line.displayed and line.source != "derived"


def printEvent(event: dict[str, Any]) -> None:
    # ASCII JSON survives Windows pipes; decoding restores the original Unicode labels.
    print(json.dumps(event), flush=True)


class EventOutput:
    """Pace real scan and figure events for an interactive walkthrough, within a fixed time budget."""

    def __init__(self, delaySeconds: float = 0, maxDelaySeconds: float = PACE_BUDGET_SECONDS) -> None:
        self.delaySeconds = delaySeconds
        self.remainingDelay = maxDelaySeconds

    def __call__(self, event: dict[str, Any]) -> None:
        printEvent(event)
        if event["type"] in ("scan", "figure") and self.remainingDelay > 0:
            delay = min(self.delaySeconds, self.remainingDelay)
            if delay > 0:
                time.sleep(delay)
                self.remainingDelay -= delay


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("file", type=Path)
    parser.add_argument("year", type=int)
    parser.add_argument("--events", action="store_true")
    parser.add_argument("--town", help="Expected government name supplied by the authorized review context")
    parser.add_argument("--province", help="Expected province supplied by the authorized review context")
    parser.add_argument("--pace", type=float, default=0, help="Seconds to show each real scan or figure event")
    args = parser.parse_args()
    if not 0 <= args.pace <= 1:  # also rejects nan and inf
        parser.error("Pace must be between 0 and 1 second")
    emit = EventOutput(args.pace)
    try:
        result = scan(args.file, args.year, emit if args.events else None, args.town, args.province)
    except Exception:
        logging.exception("Report intake failed")
        if args.events:
            printEvent({"type": "failure", "code": "report_unreadable", "message": "The report could not be read. Check the file and try again."})
        sys.exit(1)
    if args.events:
        printEvent({"type": "result", "result": result})
    else:
        print(json.dumps(result))


if __name__ == "__main__":
    main()
