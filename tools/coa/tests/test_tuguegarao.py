"""Every cell of every Tuguegarao City Executive Summary highlights table, 2020 to 2025.

Expected values were typed from rendered images of each page on 26-10-29, not copied from parser output.
Each row is (current year, prior year, Increase/(Decrease)); None is a printed dash.
"""

from decimal import Decimal
from pathlib import Path

import pytest

from coa import summary
from coa.lines import load
from coa.summary import Figure, Result, confirmDigits, parseSummary, parseText, readLines

ROOT = Path(__file__).resolve().parents[3]
LINES, ALIASES = load(ROOT / "data" / "towns" / "finances" / "lines.csv")

TABLES = {
    2020: {
        "position.assets": ("3744065734.93", "3427482941.10", "316582793.83"),
        "position.liabilities": ("587306705.41", "452785418.79", "134521286.62"),
        "position.equity": ("3156759029.52", "2974697522.31", "182061507.21"),
        "performance.revenue": ("1126713464.15", "1048262291.06", "78451173.09"),
        "performance.expenses": ("922982709.81", "871651720.20", "51330989.61"),
        "performance.net_income": ("203730754.34", "176610570.86", "27120183.48"),
        "budget.appropriations_current": ("1251278635.51", "1702336084.00", "-451057448.49"),
        "budget.appropriations_continuing": (None, None, None),
        "budget.allotments_current": ("1251278635.51", "1702336084.00", "-451057448.49"),
        "budget.allotments_continuing": ("811272069.54", "302001098.26", "509270971.28"),
        "budget.obligations_current": ("921006953.56", "944487130.13", "-23480176.57"),
        "budget.obligations_continuing": ("225230517.15", "143504522.61", "81725994.54"),
    },
    2021: {
        "position.assets": ("3949804305.31", "3744065734.93", "205738570.38"),
        "position.liabilities": ("807761506.48", "587306705.41", "220454801.07"),
        "position.equity": ("3142042798.83", "3156759029.52", "-14716230.69"),
        "performance.revenue": ("1158279956.23", "1126713464.15", "31566492.08"),
        "performance.expenses": ("1040447915.13", "922982709.81", "117465205.32"),
        "performance.net_income": ("117832041.10", "203730754.34", "-85898713.24"),
        "budget.appropriations_current": ("1731649936.00", "1251278635.51", "480371300.49"),
        "budget.allotments_current": ("1731649936.00", "1251278635.51", "480371300.49"),
        "budget.allotments_continuing": ("2413608676.88", "811272069.54", "1602336607.34"),
        "budget.obligations_current": ("1042668156.87", "921006953.56", "121661203.31"),
        "budget.obligations_continuing": ("1328755086.19", "225230517.15", "1103524569.04"),
    },
    2022: {
        "position.assets": ("4275555339.91", "3949804305.31", "325751034.60"),
        "position.liabilities": ("1088761507.93", "807761506.48", "281000001.45"),
        "position.equity": ("3186793831.98", "3142042798.83", "44751033.15"),
        "performance.revenue": ("1455908021.29", "1158279956.23", "297628065.06"),
        "performance.expenses": ("1145584089.50", "1040447915.13", "105136174.37"),
        "performance.net_income": ("310323931.79", "117832041.10", "192491890.69"),
        "budget.appropriations_current": ("1512403770.00", "1731649936.00", "-219246166.00"),
        "budget.allotments_current": ("1512403770.00", "1731649936.00", "-219246166.00"),
        "budget.allotments_continuing": ("2411081921.31", "2413608676.88", "-2526755.57"),
        "budget.obligations_current": ("1044554592.87", "1042668156.87", "1886436.00"),
        # COA prints the same amount as Obligations - Current; the 2023 report repeats it as its 2022 column.
        "budget.obligations_continuing": ("1044554592.87", "1328755086.19", "-284200493.32"),
    },
    2023: {
        "position.assets": ("4755696722.35", "4275555339.91", "480141382.44"),
        "position.liabilities": ("1342001141.32", "1088761507.93", "253239633.39"),
        "position.equity": ("3413695581.03", "3186793831.98", "226901749.05"),
        "performance.revenue": ("1328465373.08", "1455908021.29", "-127442648.21"),
        "performance.expenses": ("1161796111.37", "1145584089.50", "16212021.87"),
        "performance.net_income": ("166669261.71", "310323931.79", "-143654670.08"),
        "budget.appropriations_current": ("1523807140.00", "1512403770.00", "11403370.00"),
        "budget.allotments_current": ("1523807140.00", "1512403770.00", "11403370.00"),
        "budget.allotments_continuing": ("781995422.34", "2411081921.31", "-1629086498.97"),
        "budget.obligations_current": ("1170902599.47", "1044554592.87", "126348006.60"),
        "budget.obligations_continuing": ("273990205.92", "1044554592.87", "-770564386.95"),
    },
    2024: {
        "position.assets": ("4909278026.68", "4755696722.35", "153581304.33"),
        "position.liabilities": ("1323381986.04", "1342001141.32", "-18619155.28"),
        "position.equity": ("3585896040.64", "3413695581.03", "172200459.61"),
        "performance.revenue": ("1465722146.22", "1328465373.08", "137256773.14"),
        "performance.expenses": ("1184642113.21", "1161796111.37", "22846001.84"),
        "performance.net_income": ("281080033.01", "166669261.71", "114410771.30"),
        "budget.appropriations_current": ("1568978181.74", "1523807140.00", "45171041.74"),
        "budget.allotments_current": ("1568978181.74", "1523807140.00", "45171041.74"),
        "budget.allotments_continuing": ("611297908.10", "781995422.34", "-170697514.24"),
        "budget.obligations_current": ("1249898538.69", "1170902599.47", "78995939.22"),
        "budget.obligations_continuing": ("235381805.69", "273990205.92", "-38608400.23"),
    },
    2025: {
        "position.assets": ("5157038108.96", "4909278026.68", "247760082.28"),
        "position.liabilities": ("1479779110.38", "1323381986.04", "156397124.34"),
        "position.equity": ("3677258998.58", "3585896040.64", "91362957.94"),
        "performance.revenue": ("1679019473.60", "1465722146.22", "213297327.38"),
        "performance.expenses": ("1403269517.37", "1184642113.21", "218627404.16"),
        "performance.net_income": ("275749956.23", "281080033.01", "-5330076.78"),
        "budget.appropriations_current": ("1770385132.94", "1568978181.74", "201406951.20"),
        "budget.allotments_current": ("1770385132.94", "1568978181.74", "201406951.20"),
        "budget.allotments_continuing": ("569397448.57", "611297908.10", "-41900459.53"),
        "budget.obligations_current": ("1336894842.45", "1249898538.69", "86996303.76"),
        "budget.obligations_continuing": ("210885825.73", "235381805.69", "-24495979.96"),
    },
}


def pdfPath(year: int) -> Path:
    return ROOT / "data" / "towns" / "executive-summaries" / str(year) / "Tuguegarao-City" / f"Tuguegarao-City-Executive-Summary-{year}.pdf"


def cells(result: Result, year: int) -> dict[tuple[str, str], Decimal | None]:
    readValues = {(figure.code, figure.fiscalYear): figure.value for figure in result.figures}
    found = {}
    for code in TABLES[year]:
        found[(code, "current")] = readValues.get((code, year))
        found[(code, "prior")] = readValues.get((code, year - 1))
        found[(code, "change")] = result.changes.get(code)
    return found


def expectedCells(year: int) -> dict[tuple[str, str], Decimal | None]:
    return {
        (code, column): None if text is None else Decimal(text)
        for code, row in TABLES[year].items()
        for column, text in zip(("current", "prior", "change"), row)
    }


@pytest.mark.parametrize("year", TABLES)
def testReadsEveryCellOfTheHighlightsTable(year):
    result = parseSummary(pdfPath(year), year, LINES, ALIASES)

    assert cells(result, year) == expectedCells(year)
    assert [issue for issue in result.issues if issue.severity in ("error", "warning")] == []


@pytest.mark.parametrize("year", TABLES)
def testBothPdfEnginesReadTheSameFigures(year):
    readings = [parseText(readLines(pdfPath(year), engine), year, LINES, ALIASES) for engine in summary.ENGINES]

    assert cells(readings[0], year) == cells(readings[1], year) == expectedCells(year)


@pytest.mark.parametrize("year", [2021, 2022])
def testAppropriationsWrappedOntoTwoLinesAreCurrentNotUnspecified(year):
    variants = {figure.variant for figure in parseSummary(pdfPath(year), year, LINES, ALIASES).figures if figure.code == "budget.appropriations_current"}

    assert variants == {None}


def testFallsBackToPdfiumWhenPdfplumberFails(monkeypatch):
    original = summary.readPages

    def brokenPdfplumber(path, engine):
        if engine == "pdfplumber":
            raise OSError("simulated unreadable file")
        return original(path, engine)

    monkeypatch.setattr(summary, "readPages", brokenPdfplumber)
    result = parseSummary(pdfPath(2025), 2025, LINES, ALIASES)

    assert cells(result, 2025) == expectedCells(2025)
    assert {issue.kind for issue in result.issues} >= {"engine_failed", "read_with_fallback_engine"}


def testFlagsAValueThatTheOtherEngineDoesNotSeeOnThePage():
    misread = Figure("performance.revenue", 2025, Decimal("1679019437.60"), None, "Income", 2)

    issues = confirmDigits(Result([misread], [], {}), pdfPath(2025), "pdfium").issues

    assert [(issue.severity, issue.kind) for issue in issues] == [("error", "engine_disagreement")]
