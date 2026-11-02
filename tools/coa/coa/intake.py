"""File-scoped intake for one uploaded report. Prints the result as JSON; writes archive members only beneath the caller's output directory."""

import argparse
import hashlib
import json
import zipfile
from dataclasses import asdict
from pathlib import Path

from . import EXTRACTOR_VERSION
from .archives import MEMBERS
from .lines import load
from .summary import parseSummary
from .workbook import readFs, readAnnex


def scan(path, kind, year, output, maxMembers, maxBytes):
    lines, aliases = load(Path(__file__).resolve().parents[3] / "data/towns/finances/lines.csv")
    documents = []
    if kind == "archive":
        with zipfile.ZipFile(path) as archive:
            members = archive.infolist()
            if len(members) > maxMembers or sum(item.file_size for item in members) > maxBytes:
                raise ValueError("Archive exceeds the configured extraction limits")
            for childKind, (pattern, _) in MEMBERS.items():
                selected = [item for item in members if pattern.search(item.filename)]
                if len(selected) > 1:
                    raise ValueError("Archive contains ambiguous statement workbooks")
                for item in selected:
                    data = archive.read(item)
                    child = output / (childKind + ".xlsx")
                    child.write_bytes(data)
                    documents.append((childKind, child, item.filename))
    else:
        documents.append((kind, path, None))
    figures, issues, children = [], [], []
    for documentKind, document, member in documents:
        if document.suffix.lower() == ".xlsx":
            with zipfile.ZipFile(document) as workbook:
                if sum(item.file_size for item in workbook.infolist()) > maxBytes:
                    raise ValueError("Workbook exceeds the configured extraction limit")
        reader = {"executive_summary": parseSummary, "fs_workbook": readFs, "annex_workbook": readAnnex}.get(documentKind)
        if reader is None:
            continue
        result = reader(document, year, lines, aliases)
        if member:
            children.append({"kind": documentKind, "name": document.name, "member": member,
                "sha256": hashlib.sha256(document.read_bytes()).hexdigest()})
        issues += [asdict(issue) for issue in result.issues]
        for figure in result.figures:
            if not lines[figure.code].displayed or lines[figure.code].source == "derived":
                continue
            figures.append({"line_code": figure.code, "fiscal_year": figure.fiscalYear,
                "basis": getattr(figure, "basis", "actual"), "value": f"{figure.value:.2f}",
                "source_label": figure.sourceLabel, "location": getattr(figure, "location", None) or f"page {figure.page}",
                "definition_variant": figure.variant, "inputs": list(getattr(figure, "inputs", [])),
                "document_kind": documentKind, "member": member})
    return {"parser_version": EXTRACTOR_VERSION, "figures": figures, "issues": issues, "children": children}


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("file", type=Path)
    parser.add_argument("kind", choices=["executive_summary", "fs_workbook", "annex_workbook", "archive"])
    parser.add_argument("year", type=int)
    parser.add_argument("output", type=Path)
    parser.add_argument("--members", type=int, required=True)
    parser.add_argument("--bytes", type=int, required=True)
    args = parser.parse_args()
    args.output.mkdir(parents=True, exist_ok=True)
    print(json.dumps(scan(args.file, args.kind, args.year, args.output, args.members, args.bytes)))


if __name__ == "__main__":
    main()
