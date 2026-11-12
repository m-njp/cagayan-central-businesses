"""Read one uploaded report. Default output is JSON; --events adds live JSON-line progress."""

import argparse
import hashlib
import json
import logging
import sys
import time
import zipfile
from dataclasses import asdict
from pathlib import Path
from typing import Any

from . import EXTRACTOR_VERSION
from .archives import MEMBERS
from .checks import documentChecks
from .confidence import confidenceScore
from .lines import Line, load
from .progress import Progress, report
from .summary import family, parseSummary
from .workbook import readFs, readAnnex

READERS = {"executive_summary": parseSummary, "fs_workbook": readFs, "annex_workbook": readAnnex}
PACE_BUDGET_SECONDS = 20  # total pacing per run, well inside the queue job timeout


def inspectDocuments(path: Path, kind: str, output: Path, maxMembers: int, maxBytes: int) -> list[tuple[str, Path, str | None]]:
    """Never use archive filenames as output paths, even when a member contains ../."""
    if kind != "archive":
        documents = [(kind, path, None)]
    else:
        documents = []
        with zipfile.ZipFile(path) as archive:
            members = archive.infolist()
            if len(members) > maxMembers or sum(item.file_size for item in members) > maxBytes:
                raise ValueError("Archive exceeds the configured extraction limits")
            selectedMembers = []
            for childKind, (pattern, _) in MEMBERS.items():
                selected = [item for item in members if pattern.search(item.filename)]
                if len(selected) > 1:
                    raise ValueError("Archive contains ambiguous statement workbooks")
                selectedMembers.extend((childKind, item) for item in selected)
            for childKind, item in selectedMembers:
                child = output / (childKind + ".xlsx")
                child.write_bytes(archive.read(item))
                documents.append((childKind, child, item.filename))
    # Inspect every workbook before reading any financial records.
    for _, document, _ in documents:
        if document.suffix.lower() == ".xlsx":
            with zipfile.ZipFile(document) as workbook:
                if sum(item.file_size for item in workbook.infolist()) > maxBytes:
                    raise ValueError("Workbook exceeds the configured extraction limit")
    return documents


def serializeFigure(figure: Any, documentKind: str, member: str | None) -> dict[str, Any]:
    return {"line_code": figure.code, "fiscal_year": figure.fiscalYear,
        "basis": getattr(figure, "basis", "actual"), "value": f"{figure.value:.2f}",
        "source_label": figure.sourceLabel, "location": getattr(figure, "location", None) or f"page {figure.page}",
        "definition_variant": figure.variant, "inputs": list(getattr(figure, "inputs", [])),
        "document_kind": documentKind, "member": member}


def scan(path: Path, kind: str, year: int, output: Path, maxMembers: int, maxBytes: int, progress: Progress = None, expectedTown: str | None = None, expectedProvince: str | None = None) -> dict[str, Any]:
    sourceHash = hashlib.sha256(path.read_bytes()).hexdigest()
    lines, aliases = load(Path(__file__).resolve().parents[3] / "data/towns/finances/lines.csv")
    report(progress, "stage", stage="inspecting", message="Checking report contents and extraction limits.")
    documents = inspectDocuments(path, kind, output, maxMembers, maxBytes)
    figures, issues, children, evidence = [], [], [], []
    for documentKind, document, member in documents:
        def documentProgress(event: dict[str, Any]) -> None:
            if event["type"] == "figure" and not isDisplayed(event["figure"]["line_code"], lines):
                return
            report(progress, event["type"], **{key: value for key, value in event.items() if key != "type"}, document_kind=documentKind)

        report(progress, "stage", stage="scanning", message=f"Reading {documentKind.replace('_', ' ')}.")
        if documentKind == 'executive_summary':
            result = parseSummary(document, year, lines, aliases, documentProgress if progress else None, expectedTown, expectedProvince)
            result.issues += documentChecks(result.figures, result.changes, year)
        else:
            result = READERS[documentKind](document, year, lines, aliases, documentProgress if progress else None,
                                           expectedTown=expectedTown, expectedProvince=expectedProvince)
        documentHash = hashlib.sha256(document.read_bytes()).hexdigest()
        evidence.extend({**item, 'document_kind': documentKind, 'document_sha256': documentHash} for item in getattr(result, 'evidence', []))
        if not result.figures and sum(issue.kind == 'engine_failed' for issue in result.issues) == 2:
            raise ValueError("Neither PDF reader could open this report")
        if member:
            children.append({"kind": documentKind, "name": document.name, "member": member,
                "sha256": hashlib.sha256(document.read_bytes()).hexdigest()})
        for issue in result.issues:
            serialized = {**asdict(issue), 'family': family(issue.kind), 'document_kind': documentKind, 'document_sha256': documentHash}
            issues.append(serialized)
            report(progress, "issue", issue=serialized, document_kind=documentKind)
        documentFigures = [serializeFigure(figure, documentKind, member) for figure in result.figures if isDisplayed(figure.code, lines)]
        figures.extend(documentFigures)
        report(progress, "replace", document_kind=documentKind, figures=documentFigures)
    if not documents:
        issue = {"severity": "error", "kind": "no_recognized_documents", "code": "", "message": "No recognized statement workbooks were found."}
        issues.append(issue)
        report(progress, "issue", issue=issue)
    if hashlib.sha256(path.read_bytes()).hexdigest() != sourceHash:
        raise ValueError('The input source changed during extraction; no final result is safe to save')
    return {"parser_version": EXTRACTOR_VERSION, "source_sha256": sourceHash,
            "figures": figures, "issues": issues, "children": children, "evidence": evidence,
            "confidence": confidenceScore(figures, issues)}


def isDisplayed(code: str, lines: dict[str, Line]) -> bool:
    return lines[code].displayed and lines[code].source != "derived"


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
    parser.add_argument("kind", choices=list(READERS) + ["archive"])
    parser.add_argument("year", type=int)
    parser.add_argument("output", type=Path)
    parser.add_argument("--members", type=int, required=True)
    parser.add_argument("--bytes", type=int, required=True)
    parser.add_argument("--events", action="store_true")
    parser.add_argument("--town", help="Expected government name supplied by the authorized review context")
    parser.add_argument("--province", help="Expected province supplied by the authorized review context")
    parser.add_argument("--pace", type=float, default=0, help="Seconds to show each real scan or figure event")
    args = parser.parse_args()
    if not 0 <= args.pace <= 1:  # also rejects nan and inf
        parser.error("Pace must be between 0 and 1 second")
    emit = EventOutput(args.pace)
    args.output.mkdir(parents=True, exist_ok=True)
    try:
        result = scan(args.file, args.kind, args.year, args.output, args.members, args.bytes, emit if args.events else None, args.town, args.province)
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
