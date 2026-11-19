"""Draw a reproducible random sample of extracted figures with the source evidence a person needs to confirm each one.

    composer data:finances:sample -- --size 60 --seed 26111

The sample is stratified by report year so every layout era is represented. Each row repeats what the Executive Summary
prints around the figure: the printed line on the cited page that contains the amount. A row where the evidence
does not show the town, year, line or amount is an extraction error, and the measured error rate is the parser's accuracy.
"""

import argparse
import csv
import random
import re
import sys
from collections import defaultdict
from pathlib import Path

import pdfplumber

from .lines import lineLabel

ROOT = Path(__file__).resolve().parents[3]
TOWNS = ROOT / "data/towns"
FINANCES = TOWNS / "finances"
COLUMNS = ["town", "report_year", "kind", "fiscal_year", "line", "value", "location", "title", "row_label", "column_heading",
           "source_value", "automatic_checks"]


def documentFiles() -> dict[tuple[str, str, str], Path]:
    files = {}
    with (FINANCES / "documents.csv").open(encoding="utf-8-sig", newline="") as handle:
        for row in csv.DictReader(handle):
            if row["availability"] == "available" and row["file"]:
                files[(row["psgc_code"], row["fiscal_year"], row["kind"])] = TOWNS / row["file"]
    return files


def summaryEvidence(path: Path, location: str, value: str) -> dict:
    page = int(re.search(r"\d+", location).group())
    amount = f"{abs(float(value)):,.2f}"
    with pdfplumber.open(path) as pdf:
        text = pdf.pages[page - 1].extract_text() or ""
        title = " / ".join(text.splitlines()[:3])
    printed = next((line for line in text.splitlines() if amount in line), "")
    return {"title": title[:160], "row_label": printed, "column_heading": "", "source_value": amount if printed else "NOT FOUND ON PAGE"}


def checksFor(rows: list[dict]) -> dict[tuple, str]:
    """The findings that name each figure, so the reviewer sees what the automatic checks already said."""
    found = defaultdict(list)
    for path in (FINANCES / "issues").glob("*.csv"):
        with path.open(encoding="utf-8-sig", newline="") as handle:
            for issue in csv.DictReader(handle):
                if issue["line_code"] and issue["severity"] in ("error", "warning"):
                    found[(issue["psgc_code"], issue["reported_in_year"], issue["document_kind"], issue["line_code"])].append(issue["kind"])
    return {key: ", ".join(sorted(set(kinds))) for key, kinds in found.items()}


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("--size", type=int, default=60)
    parser.add_argument("--seed", type=int, default=26111)
    parser.add_argument("--output", type=Path, default=ROOT / "storage/app/source-review/coa/accuracy-sample.csv")
    args = parser.parse_args(argv)

    rows = []
    for path in sorted((FINANCES / "extracted").glob("*.csv")):
        with path.open(encoding="utf-8-sig", newline="") as handle:
            rows += list(csv.DictReader(handle))
    strata = defaultdict(list)
    for row in rows:
        era = "2014-2017" if int(row["reported_in_year"]) <= 2017 else "2018-2021" if int(row["reported_in_year"]) <= 2021 else "2022-2025"
        strata[(row["document_kind"], era)].append(row)
    generator = random.Random(args.seed)
    perStratum = max(1, args.size // len(strata))
    picked = [row for key in sorted(strata) for row in generator.sample(strata[key], min(perStratum, len(strata[key])))]

    files = documentFiles()
    checks = checksFor(picked)
    sample = []
    for row in picked:
        path = files[(row["psgc_code"], row["reported_in_year"], row["document_kind"])]
        try:
            evidence = summaryEvidence(path, row["location"], row["value"])
        except (KeyError, ValueError, AttributeError, IndexError) as error:
            # The cited place cannot be found in the registered file: that itself is a finding for the reviewer.
            evidence = {"title": "", "row_label": "", "column_heading": "", "source_value": f"CITATION NOT FOUND in {path.name}: {error}"}
        sample.append({"town": row["town"], "report_year": row["reported_in_year"], "kind": row["document_kind"],
                       "fiscal_year": row["fiscal_year"], "line": lineLabel(row["line_code"]), "value": row["value"],
                       "location": row["location"], **evidence,
                       "automatic_checks": checks.get((row["psgc_code"], row["reported_in_year"], row["document_kind"], row["line_code"]), "")})
    args.output.parent.mkdir(parents=True, exist_ok=True)
    with args.output.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=COLUMNS)
        writer.writeheader()
        writer.writerows(sample)
    print(f"Sampled {len(sample)} figures from {len(rows)} across {len(strata)} kind and era groups: {args.output}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
