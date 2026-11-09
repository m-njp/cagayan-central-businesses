"""Progress is observable before completion and never changes financial output."""

import json
import os
import subprocess
import sys
from pathlib import Path

from coa.intake import EventOutput, scan
from coa.lines import load
from coa import summary, workbook

ROOT = Path(__file__).resolve().parents[3]
FS = ROOT / "data/towns/financial-statements/2025/Tuguegarao-City/Tuguegarao-City-FS-2025.xlsx"
PDF = ROOT / "data/towns/executive-summaries/2025/Tuguegarao-City/Tuguegarao-City-Executive-Summary-2025.pdf"
LINES, ALIASES = load(ROOT / "data/towns/finances/lines.csv")


def testProgressAndDefaultIntakeHaveIdenticalFinancialOutput(tmp_path):
    events = []

    streamed = scan(FS, "fs_workbook", 2025, tmp_path, 40, 104857600, events.append)
    plain = scan(FS, "fs_workbook", 2025, tmp_path, 40, 104857600)

    assert streamed == plain
    assert events[0]["stage"] == "inspecting"
    assert any(event["type"] == "scan" and event["location"] == "8 - Financial Performance 2025" for event in events)
    assert any(event["type"] == "figure" and event["figure"]["value"] == "430895820.24" for event in events)
    assert events[-1]["type"] == "replace"
    assert events[-1]["figures"] == streamed["figures"]


def testEachWorkbookSheetIsReadOnceAndFileCanBeRemoved(tmp_path, monkeypatch):
    source = tmp_path / "source.xlsx"
    source.write_bytes(FS.read_bytes())
    original = workbook.grid
    scanned = []

    def counted(sheet):
        scanned.append(sheet.title)
        return original(sheet)

    monkeypatch.setattr(workbook, "grid", counted)
    result = workbook.readFs(source, 2025, LINES, ALIASES)
    source.unlink()

    assert result.figures
    assert len(scanned) == len(set(scanned))
    assert not source.exists()


def testFallbackDoesNotReadAnEngineTwice(monkeypatch):
    original = summary.readPages
    calls = []

    def incomplete(path, engine):
        calls.append(engine)
        pages = original(path, engine)
        return [(number, text.replace("Financial Highlights", "Missing Heading")) for number, text in pages] if engine == "pdfplumber" else pages

    monkeypatch.setattr(summary, "readPages", incomplete)
    result = summary.parseSummary(PDF, 2025, LINES, ALIASES)

    assert result.figures
    assert calls.count("pdfplumber") == 1
    assert calls.count("pdfium") == 1


def testCommandFlushesEventsBeforeExitAndKeepsPlainJsonCompatible(tmp_path):
    command = [sys.executable, "-u", "-m", "coa.intake", str(PDF), "executive_summary", "2025", str(tmp_path), "--members", "40", "--bytes", "104857600"]
    # communicate() must not bypass bytes read ahead by a buffered text readline().
    process = subprocess.Popen(command + ["--events"], stdout=subprocess.PIPE, stderr=subprocess.PIPE, bufsize=0)
    try:
        first = json.loads(process.stdout.readline())
        stillRunning = process.poll() is None
        remaining, error = process.communicate(timeout=60)
    finally:
        if process.poll() is None:
            process.kill()
            process.communicate()

    assert first["stage"] == "inspecting"
    assert stillRunning
    assert process.returncode == 0, error
    events = [json.loads(line) for line in remaining.splitlines()]
    assert events[-1]["type"] == "result"
    assert sum(event["type"] == "result" for event in events) == 1
    plain = subprocess.run(command, capture_output=True, text=True, encoding="utf-8", check=True)
    assert json.loads(plain.stdout) == events[-1]["result"]


def testUnreadableFileEmitsSafeFailureWithoutResult(tmp_path):
    source = tmp_path / "broken.pdf"
    source.write_text("not a PDF", encoding="utf-8")
    process = subprocess.run([sys.executable, "-m", "coa.intake", str(source), "executive_summary", "2025", str(tmp_path), "--members", "40", "--bytes", "104857600", "--events"], capture_output=True, text=True, encoding="utf-8")

    events = [json.loads(line) for line in process.stdout.splitlines()]
    assert process.returncode != 0
    assert events[-1]["type"] == "failure"
    assert str(tmp_path) not in events[-1]["message"]
    assert not any(event["type"] == "result" for event in events)


def testWindowsPipeEncodingPreservesUnicodeSourceLabels(tmp_path):
    process = subprocess.run([sys.executable, "-m", "coa.intake", str(PDF), "executive_summary", "2025", str(tmp_path), "--members", "40", "--bytes", "104857600", "--events"],
        env={**os.environ, "PYTHONIOENCODING": "cp1252"}, capture_output=True, check=True)

    events = [json.loads(line) for line in process.stdout.decode("utf-8").splitlines()]
    result = events[-1]["result"]
    assert len(result["figures"]) == 20
    assert next(row for row in result["figures"] if row["line_code"] == "performance.revenue" and row["fiscal_year"] == 2025)["value"] == "1679019473.60"


def testInteractivePacingDelaysOnlyRealWorkAndNeverExceedsItsBudget(monkeypatch, capsys):
    sleeps = []
    monkeypatch.setattr("coa.intake.time.sleep", sleeps.append)
    emit = EventOutput(0.5, 0.75)
    events = [{"type": "stage", "stage": "scanning"}, {"type": "scan", "location": "page 1"},
        {"type": "figure", "figure": {"value": "20.00"}}, {"type": "scan", "location": "page 2"}, {"type": "result", "result": {}}]

    for event in events:
        emit(event)

    assert sleeps == [0.5, 0.25]
    assert [json.loads(line) for line in capsys.readouterr().out.splitlines()] == events
