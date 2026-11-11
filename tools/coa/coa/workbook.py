"""Reads revenue sources, expense types, sector spending and local revenue detail from COA statement workbooks.

Sheet names differ in every town ("Performance", "8. Consolidated Fin Per", "B-1 (Detailed SFPer- GF)"),
so sheets are chosen by their title rows and the labels they contain, never by name.
"""

import re
import warnings
from dataclasses import dataclass, field
from decimal import ROUND_HALF_UP, Decimal
from pathlib import Path

import openpyxl
from openpyxl.utils import get_column_letter

from .lines import Alias, Line, match, normalize
from .summary import Issue
from .progress import Progress, preview, report

CENTS = Decimal("0.01")
TITLE_ROWS = 12
# Statements split this line over several rows ("Gains", "Miscellaneous Income"); they add up to one figure.
SUMMED = {"revenue.other"}
SECTOR_CLASSES = {
    "personnel": re.compile(r"^person(?:n)?al serv"),
    "mooe": re.compile(r"^(?:maintenance|mooe\b)"),
    "capital_outlay": re.compile(r"^capital outlay"),
    "financial": re.compile(r"^(?:financial expenses?|amortization|interest|loan amortization)"),
}


@dataclass(frozen=True)
class Found:
    code: str
    fiscalYear: int
    value: Decimal
    basis: str
    variant: str | None
    sourceLabel: str
    location: str
    inputs: tuple[dict[str, str], ...] = ()


@dataclass
class Result:
    figures: list[Found] = field(default_factory=list)
    issues: list[Issue] = field(default_factory=list)


def amount(cell) -> Decimal | None:
    if isinstance(cell, bool) or not isinstance(cell, (int, float)):
        return None
    return Decimal(str(cell)).quantize(CENTS, rounding=ROUND_HALF_UP)


def load(path: Path):
    with warnings.catch_warnings():
        warnings.simplefilter("ignore")
        return openpyxl.load_workbook(path, read_only=True, data_only=True)


def grid(sheet) -> list[tuple]:
    return [tuple(row) for row in sheet.iter_rows(values_only=True)]


def title(rows: list[tuple]) -> str:
    return " ".join(str(cell) for row in rows[:TITLE_ROWS] for cell in row if isinstance(cell, str)).lower()


def labelOf(row: tuple, limit: int = 6) -> tuple[str, int] | None:
    """The first text cell left of the amounts. Some statements indent labels by column."""
    for index, cell in enumerate(row[:limit]):
        if isinstance(cell, str) and re.search(r"[a-z]", cell, re.I) and cell.strip() not in ("₱", "P"):
            return cell.strip(), index
    return None


def yearColumns(rows: list[tuple], reportYear: int) -> dict[int, int]:
    """Map each year to the column holding its amounts. A peso sign can push amounts one cell right of the header."""
    for row in rows[:TITLE_ROWS + 4]:
        headers = {}
        for index, cell in enumerate(row):
            text = str(cell).strip() if cell is not None else ""
            if text in (str(reportYear), str(reportYear - 1)) or (isinstance(cell, (int, float)) and cell in (reportYear, reportYear - 1)):
                headers[int(float(text))] = index
        if reportYear in headers:
            columns = {}
            ordered = sorted(headers.items(), key=lambda item: item[1])
            for position, (year, start) in enumerate(ordered):
                stop = ordered[position + 1][1] if position + 1 < len(ordered) else start + 3
                candidates = range(start, max(start + 1, min(stop, start + 3)))
                columns[year] = max(candidates, key=lambda column: sum(1 for r in rows if column < len(r) and amount(r[column]) is not None))
            return columns
    return {}


def statedYear(rows: list[tuple]) -> int | None:
    found = re.search(r"year ended[^0-9]*(?:december\s*31,?\s*)?(20\d\d)", title(rows))
    return int(found.group(1)) if found else None


def pickSheets(book, scores: dict, progress: Progress = None) -> dict:
    """Score all required statement types in one pass; keep only the best sheet for each."""
    best = {}
    for number, sheet in enumerate(book.worksheets, start=1):
        report(progress, "scan", stage="scanning", message=f"Scanning sheet {number} of {len(book.sheetnames)}.", location=sheet.title, current=number, total=len(book.sheetnames))
        rows = grid(sheet)
        for kind, score in scores.items():
            points = score(sheet.title, rows)
            if points > 0 and (kind not in best or points > best[kind][0]):
                best[kind] = (points, sheet.title, rows)
    return {kind: (best[kind][1], best[kind][2]) if kind in best else None for kind in scores}


def performanceScore(name: str, rows: list[tuple]) -> int:
    text = title(rows)
    if "performance" not in text or "detailed" in text or "detailed" in name.lower():
        return 0
    labels = {normalize(found[0]) for row in rows if (found := labelOf(row))}
    return sum(1 for wanted in ("tax revenue", "personnel services", "total revenue") if wanted in labels) + ("consolidated" in text)


def generalFundColumns(rows: list[tuple], reportYear: int) -> dict[int, int]:
    """Multi-fund sheets put General Fund, SEF, Trust Fund and Total side by side; use the General Fund cells."""
    for position, row in enumerate(rows[:TITLE_ROWS + 4]):
        funds = [index for index, cell in enumerate(row) if isinstance(cell, str) and re.search(r"fund|total|consolidated|\bsef\b", cell, re.I)]
        general = [index for index in funds if re.fullmatch(r"\s*gen(?:eral|\.)?\s*fund\s*", str(row[index]), re.I)]
        if not general or len(funds) < 2:
            continue
        start = general[0]
        stop = min([index for index in funds if index > start], default=start + 2)
        columns = {}
        for nearby in rows[max(0, position - 2):position + 3]:
            for index, cell in enumerate(nearby):
                year = re.search(r"(20\d\d)", str(cell)) if cell is not None else None
                if year and start <= index < stop and int(year.group(1)) in (reportYear, reportYear - 1):
                    columns.setdefault(int(year.group(1)), index)
        if reportYear not in columns:
            columns[reportYear] = start
        return columns
    return {}


def annexColumns(name: str, rows: list[tuple], reportYear: int) -> dict[int, int]:
    text = title(rows) + " " + name.lower()
    if "performance" not in text and "sfper" not in text and "sfpe" not in text:
        return {}
    if columns := generalFundColumns(rows, reportYear):
        return columns
    if re.search(r"special education|trust fund|\bsef\b|\btf\b", text):
        return {}
    return yearColumns(rows, reportYear)


def annexScore(name: str, rows: list[tuple], reportYear: int) -> int:
    if not annexColumns(name, rows, reportYear):
        return 0
    labels = {normalize(found[0]) for row in rows if (found := labelOf(row))}
    return sum(1 for wanted in ("business tax", "community tax", "permit fees", "real property tax - basic", "real property tax-basic") if wanted in labels)


def budgetScore(name: str, rows: list[tuple]) -> int:
    text = title(rows) + " " + name.lower()
    return 2 if "comparison of budget" in text or re.search(r"scb?a?baa", text) else 0


def readLines(rows, columns, sheet, file, source, lines, aliases, progress: Progress = None):
    """Keep the exact cell and signed amount for every year and summed component."""
    found = {}
    for number, row in enumerate(rows, start=1):
        labelled = labelOf(row, min(columns.values()))
        if not labelled:
            continue
        hit = match(labelled[0], aliases, source, lines)
        if not hit or (hit[0] in found and hit[0] not in SUMMED):
            continue
        values = {year: amount(row[column]) for year, column in columns.items() if column < len(row)}
        values = {year: value for year, value in values.items() if value is not None}
        if not values:
            continue
        inputs = {year: [{"location": f"{sheet}!{get_column_letter(columns[year] + 1)}{number}", "value": f"{value:.2f}"}] for year, value in values.items()}
        if hit[0] in found:
            earlier, earlierLabel, earlierInputs = found[hit[0]]
            values = {year: earlier.get(year, Decimal(0)) + values.get(year, Decimal(0)) for year in earlier.keys() | values.keys()}
            inputs = {year: earlierInputs.get(year, []) + inputs.get(year, []) for year in values}
            found[hit[0]] = (values, f"{earlierLabel} + {labelled[0]}", inputs)
        else:
            found[hit[0]] = (values, labelled[0], inputs)
        if progress is not None:
            for year, value in found[hit[0]][0].items():
                location = citation(found[hit[0]][2][year])
                report(progress, "scan", stage="scanning", message=f"Reading {lines[hit[0]].label}.", location=location, current=number, total=len(rows))
                preview(progress, Found(hit[0], year, value, "actual", hit[1], labelled[0], location))
    return found


def citation(inputs):
    return "; ".join(item["location"] for item in inputs)


def readFs(path: Path, reportYear: int, lines: dict[str, Line], aliases: list[Alias], progress: Progress = None) -> Result:
    result = Result()
    book = load(path)
    try:
        selected = pickSheets(book, {"performance": performanceScore, "budget": budgetScore}, progress)
    finally:
        book.close()
    performance = selected["performance"]
    report(progress, "stage", stage="checking", message="Matching statement lines and checking totals.")
    if performance is None:
        result.issues.append(Issue("error", "sheet_not_found", "", f"{path.name}: no Statement of Financial Performance sheet"))
    else:
        readPerformance(result, path.name, *performance, reportYear, lines, aliases, progress)
    budget = selected["budget"]
    if budget is None:
        result.issues.append(Issue("warning", "sheet_not_found", "", f"{path.name}: no budget and actual (SCBAA) sheet"))
    else:
        readSectors(result, path.name, *budget, reportYear, lines, aliases)
    return result


def readPerformance(result: Result, file: str, sheet: str, rows, reportYear, lines, aliases, progress: Progress = None) -> None:
    columns = yearColumns(rows, reportYear)
    if reportYear not in columns:
        result.issues.append(Issue("error", "year_columns_missing", "", f"{file}!{sheet}: no {reportYear} column"))
        return
    if (year := statedYear(rows)) not in (None, reportYear):
        result.issues.append(Issue("warning", "sheet_year_mismatch", "", f"{file}!{sheet}: title says {year}"))
    found = readLines(rows, columns, sheet, file, "fs_workbook", lines, aliases, progress)
    totals = readLines(rows, columns, sheet, file, "fs_totals", lines, aliases)

    if "revenue.national_share" not in found and "revenue.tax" in found:
        result.issues.append(Issue("warning", "national_share_not_separate", "revenue.tax", f"{file}!{sheet}: no national share line, so Local Taxes may include it"))
    for code, (values, label, inputs) in found.items():
        variant = "may_include_national_share" if code == "revenue.tax" and "revenue.national_share" not in found else None
        for year, value in values.items():
            result.figures.append(Found(code, year, abs(value) if code == "expense.transfers" else value, "actual", variant, label, citation(inputs[year]), tuple(inputs[year])))

    if "fs.total_revenue" in totals:
        for year, total in totals["fs.total_revenue"][0].items():
            _, label, inputs = totals["fs.total_revenue"]
            result.figures.append(Found("fs.total_revenue", year, total, "actual", None, label, citation(inputs[year]), tuple(inputs[year])))
            parts = [values[year] for code, (values, _, _) in found.items() if code.startswith("revenue.") and year in values]
            if parts and abs(sum(parts) - total) > Decimal("1.00"):
                result.issues.append(Issue("error", "revenue_sources_mismatch", "revenue.tax", f"{year}: sources add up to {sum(parts)}, Total Revenue is {total}"))


def readSectors(result: Result, file: str, sheet: str, rows, reportYear, lines, aliases) -> None:
    final = actual = None
    for row in rows[:TITLE_ROWS + 4]:
        for index, cell in enumerate(row):
            text = re.sub(r"\s+", " ", str(cell)).strip().lower() if cell is not None else ""
            if text == "final":
                final = index
            elif text in ("actual", "actual amounts", "actual amount"):
                actual = index
    if actual is None:
        result.issues.append(Issue("error", "budget_columns_missing", "", f"{file}!{sheet}: no Actual Amounts column"))
        return
    if (year := statedYear(rows)) not in (None, reportYear):
        result.issues.append(Issue("warning", "sheet_year_mismatch", "", f"{file}!{sheet}: title says {year}, workbook is for {reportYear}"))

    sectors: dict[str, dict] = {}
    current = None
    hasStarted = False
    for number, row in enumerate(rows, start=1):
        labelled = labelOf(row, min(column for column in (actual, final) if column is not None))
        if not labelled:
            continue
        text = normalize(labelled[0])
        if re.match(r"^(expenditures?|current ap+r+opriations?)\b", text):
            hasStarted = True
            continue
        if not hasStarted:
            continue
        if re.search(r"^continuing appropriation|^sub ?total|^total\b", text):
            break
        spent = amount(row[actual]) if actual < len(row) else None
        budget = amount(row[final]) if final is not None and final < len(row) else None
        hit = match(labelled[0], aliases, "fs_scbaa", lines)
        if hit and hit[0].count(".") == 1:
            current = sectors.setdefault(hit[0], {"label": labelled[0], "row": number, "actual": Decimal(0), "budget": Decimal(0), "seen": False, "actual_inputs": [], "budget_inputs": []})
            if spent is None and budget is None:
                continue
        elif spent is None and budget is None:
            if not any(pattern.match(text) for pattern in SECTOR_CLASSES.values()):
                current = None
                if not re.match(r"^(other purposes|others)\b", text):
                    result.issues.append(Issue("info", "heading_not_in_catalog", "", f"{file}!{sheet} row {number}: '{labelled[0]}'"))
            continue
        elif current is None:
            if spent:
                result.issues.append(Issue("info", "spending_outside_catalog", "", f"{file}!{sheet} row {number}: '{labelled[0]}' {spent} is not under a catalog sector"))
            continue
        if spent is not None:
            current["actual"] += spent
            current["actual_inputs"].append({"location": f"{sheet}!{get_column_letter(actual + 1)}{number}", "value": f"{spent:.2f}"})
        if budget is not None:
            current["budget_inputs"].append({"location": f"{sheet}!{get_column_letter(final + 1)}{number}", "value": f"{budget:.2f}"})
        current["budget"] += budget or 0
        current["seen"] = True

    for code, sector in sectors.items():
        if not sector["seen"]:
            continue
        for basis, key in (("actual", "actual_inputs"), ("budget_final", "budget_inputs")):
            if sector[key]:
                result.figures.append(Found(code, reportYear, sum(Decimal(item["value"]) for item in sector[key]), basis, None, sector["label"], citation(sector[key]), tuple(sector[key])))



def readAnnex(path: Path, reportYear: int, lines: dict[str, Line], aliases: list[Alias], progress: Progress = None) -> Result:
    result = Result()
    book = load(path)
    try:
        picked = pickSheets(book, {"annex": lambda name, rows: annexScore(name, rows, reportYear)}, progress)["annex"]
    finally:
        book.close()
    report(progress, "stage", stage="checking", message="Matching General Fund lines and checking amounts.")
    if picked is None:
        result.issues.append(Issue("warning", "sheet_not_found", "", f"{path.name}: no detailed General Fund performance sheet"))
        return result
    sheet, rows = picked
    columns = annexColumns(sheet, rows, reportYear)
    if reportYear not in columns:
        result.issues.append(Issue("error", "year_columns_missing", "", f"{path.name}!{sheet}: no {reportYear} column"))
        return result
    found = readLines(rows, columns, sheet, path.name, "annex_c1", lines, aliases, progress)
    discount = found.pop("annex.rpt_discount", None)
    for code, (values, label, inputs) in found.items():
        for year, value in values.items():
            if code == "local.real_property_tax" and discount and year in discount[0]:
                value -= abs(discount[0][year])
                inputs[year] += [{**item, "value": f"{-abs(Decimal(item['value'])):.2f}"} for item in discount[2][year]]
            result.figures.append(Found(code, year, value, "actual", None, label, citation(inputs[year]), tuple(inputs[year])))
    return result
