"""Progress is observable before completion and never changes financial output."""

import json
import os
import subprocess
import sys
from pathlib import Path

from coa.intake import EventOutput, scan
from coa.confidence import confidenceScore
from coa.lines import load
from coa import summary

ROOT = Path(__file__).resolve().parents[3]
PDF = ROOT / "data/towns/executive-summaries/2025/Tuguegarao-City/Tuguegarao-City-Executive-Summary-2025.pdf"
LINES, ALIASES = load(ROOT / "data/towns/finances/lines.csv")


def testProgressAndDefaultIntakeHaveIdenticalFinancialOutput():
    events = []

    streamed = scan(PDF, 2025, events.append)
    plain = scan(PDF, 2025)

    assert streamed == plain
    assert streamed['confidence'] == confidenceScore(streamed['figures'], streamed['issues'])
    assert events[0]["stage"] == "scanning"
    assert any(event["type"] == "figure" and event["figure"]["value"] == "1679019473.60" for event in events)
    assert events[-1]["type"] == "replace"
    assert events[-1]["figures"] == streamed["figures"]


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
    command = [sys.executable, "-u", "-m", "coa.intake", str(PDF), "2025"]
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

    assert first["stage"] == "scanning"
    assert stillRunning
    assert process.returncode == 0, error
    events = [json.loads(line) for line in remaining.splitlines()]
    assert events[-1]["type"] == "result"
    assert sum(event["type"] == "result" for event in events) == 1
    plain = subprocess.run(command, capture_output=True, text=True, encoding="utf-8", check=True)
    assert json.loads(plain.stdout) == events[-1]["result"]
    result = events[-1]['result']
    assert result['confidence'] == confidenceScore(result['figures'], result['issues'])


def testUnreadableFileEmitsSafeFailureWithoutResult(tmp_path):
    source = tmp_path / "broken.pdf"
    source.write_text("not a PDF", encoding="utf-8")
    process = subprocess.run([sys.executable, "-m", "coa.intake", str(source), "2025", "--events"], capture_output=True, text=True, encoding="utf-8")

    events = [json.loads(line) for line in process.stdout.splitlines()]
    assert process.returncode != 0
    assert events[-1]["type"] == "failure"
    assert str(tmp_path) not in events[-1]["message"]
    assert not any(event["type"] == "result" for event in events)


def testWindowsPipeEncodingPreservesUnicodeSourceLabels(tmp_path):
    process = subprocess.run([sys.executable, "-m", "coa.intake", str(PDF), "2025", "--events"],
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
