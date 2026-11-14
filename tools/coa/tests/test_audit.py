"""Auditing stages evidence and reports differences without changing the finance ledger CSVs."""

import csv
import importlib.util
import json
from pathlib import Path
from types import SimpleNamespace

import pytest

from coa import audit
from coa import __main__ as batch


def figure(line="performance.revenue", fiscalYear="2014", **overrides):
    return {"psgc_code": "0201501000", "town": "Abulug", "reported_in_year": "2014",
            "document_kind": "executive_summary", "fiscal_year": fiscalYear, "line_code": line,
            "basis": "actual", "value": "100.00", "definition_variant": "", "source_label": "Income",
            "location": "page 1", "document_sha256": "a" * 64, "extractor_version": "1.3.0", "input_values": "[]"} | overrides


def sourceIssue(kind="engine_disagreement", severity="error", line="performance.revenue", **overrides):
    return {"psgc_code": "0201501000", "town": "Abulug", "reported_in_year": "2014", "severity": severity,
            "kind": kind, "line_code": line, "message": "Inspect this source row.", "location": "page 1",
            "document_kind": "executive_summary", "document_sha256": "a" * 64, "fiscal_year": "2013"} | overrides


def writeCsv(path, columns, rows):
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8", newline="") as output:
        writer = csv.DictWriter(output, fieldnames=columns, lineterminator="\n")
        writer.writeheader()
        writer.writerows(rows)


@pytest.fixture
def auditFixture(tmp_path, monkeypatch):
    canonical = tmp_path / "data/towns/finances"
    sources = tmp_path / "data/towns/executive-summaries/sources.csv"
    workbooks = tmp_path / "data/towns/financial-statements"
    rows = [figure(line, year) for line in ("performance.revenue", "performance.expenses") for year in ("2014", "2013")]
    writeCsv(canonical / "extracted/2014.csv", batch.EXTRACTED_COLUMNS, rows)
    writeCsv(canonical / "reviews.csv", ["decision"], [{"decision": "verified"}])
    writeCsv(sources, ["psgc_code", "town", "year", "sha256"], [{
        "psgc_code": "0201501000", "town": "Abulug", "year": "2014", "sha256": "a" * 64,
    }])
    monkeypatch.setattr(audit, "ROOT", tmp_path)
    monkeypatch.setattr(audit, "CANONICAL", canonical)
    monkeypatch.setattr(audit, "SOURCES", sources)
    monkeypatch.setattr(batch, "WORKBOOKS", workbooks)
    state = {"rows": rows, "issues": [], "called": 0}

    def fakeExtract(output):
        state["called"] += 1
        writeCsv(output / "extracted/2014.csv", batch.EXTRACTED_COLUMNS, state["rows"])
        writeCsv(output / "issues/2014.csv", batch.ISSUE_COLUMNS, state["issues"])
        writeCsv(output / "documents.csv", batch.DOCUMENT_COLUMNS, [])
        manifest = {path.relative_to(output).as_posix(): audit.digest(path) for path in sorted(output.rglob("*.csv"))}
        (output / "run.json").write_text(json.dumps({"parser_version": "1.3.0", "canonical_updated": False, "files": manifest}), encoding="utf-8")
        if state.get("tamper"):
            (output / "extracted/2014.csv").write_text("changed", encoding="utf-8")
        if state.get("extra"):
            (output / "unexpected.csv").write_text("unexpected\n", encoding="utf-8")
        if state.get("mutateBaseline"):
            (canonical / "reviews.csv").write_text("decision\nrejected\n", encoding="utf-8")
        return True

    monkeypatch.setattr(batch, "extract", fakeExtract)
    return state, canonical, sources


def testAuditLeavesCanonicalCsvsUnchangedAndWritesSourceBoundReviewItems(auditFixture):
    state, canonical, sources = auditFixture
    before = audit.snapshotCsv(canonical)
    sourceHash = audit.digest(sources)
    state["issues"] = [sourceIssue(), sourceIssue("net_income_mismatch", line="performance.net_income"),
                       sourceIssue("column_order_changed", "warning", "")]

    result = audit.runAudit(Path("storage/audit"))

    output = Path(result["output"])
    assert audit.snapshotCsv(canonical) == before
    assert audit.digest(sources) == sourceHash
    assert result["canonicalUnchanged"] is True
    assert result["status"] == "needs_review"
    assert result["comparison"]["counts"] == {"baseline": 4, "staged": 4, "added": 0, "removed": 0,
                                             "changed": 0, "valueChanges": 0, "definitionChanges": 0,
                                             "locationChanges": 0, "documentHashChanges": 0}
    assert result["coverage"]["requiredCells"] == 4
    assert result["coverage"]["candidateCells"] == 4
    assert result["coverage"]["evidenceClearCells"] == 3
    with (output / "review-items.csv").open(encoding="utf-8", newline="") as saved:
        items = list(csv.DictReader(saved))
    assert {item["action"] for item in items} == {"resolve_extraction_evidence", "inspect_source_arithmetic", "inspect_warning"}
    assert all(item["document_sha256"] == "a" * 64 for item in items)
    assert all(item["location"] == "page 1" for item in items)
    assert not (output / ".audit-running").exists()
    artifact = json.loads((output / "audit-manifest.json").read_text())
    assert artifact["files"]["comparison.json"] == audit.digest(output / "comparison.json")
    assert artifact["files"]["review-items.csv"] == audit.digest(output / "review-items.csv")


def testComparisonSeparatesFinancialChangesFromCitationAndVersionChanges():
    old = figure()
    removed = figure("budget.obligations_current")
    changed = figure(value="101.00", definition_variant="different_scope", location="page 2",
                     document_sha256="b" * 64, extractor_version="1.4.0")
    added = figure(reported_in_year="2015")
    before = {tuple(row[field] for field in audit.KEY_FIELDS): row for row in [old, removed]}
    after = {tuple(row[field] for field in audit.KEY_FIELDS): row for row in [changed, added]}

    result = audit.compareObservations(before, after)

    assert result["counts"] == {"baseline": 2, "staged": 2, "added": 1, "removed": 1, "changed": 1,
                                "valueChanges": 1, "definitionChanges": 1, "locationChanges": 1, "documentHashChanges": 1}
    assert result["added"][0]["reported_in_year"] == "2015"
    assert result["added"][0]["fiscal_year"] == "2014"
    assert result["changed"][0]["fields"]["value"] == {"before": "100.00", "after": "101.00"}
    assert result == audit.compareObservations(dict(reversed(list(before.items()))), dict(reversed(list(after.items()))))


def testCoverageUsesReportAndComparativeYearsAndDoesNotBorrowAnotherDocument(auditFixture):
    state, _, sources = auditFixture
    rows = [figure("performance.revenue", "2014"), figure("performance.revenue", "2013"),
            figure("performance.expenses", "2014"), figure("performance.expenses", "2013", reported_in_year="2015")]
    staged = {tuple(row[field] for field in audit.KEY_FIELDS): row for row in rows}
    issues = [sourceIssue(), sourceIssue(document_kind="fs_workbook", line="performance.expenses", fiscal_year="2014")]

    result = audit.summaryCoverage(audit.readRows(sources), staged, issues)

    assert result["candidateCells"] == 3
    assert result["evidenceClearCells"] == 2
    assert result["rows"][0]["missingCells"] == [{"lineCode": "performance.expenses", "fiscalYear": 2013}]
    assert result["rows"][0]["blockedCells"] == [{"lineCode": "performance.revenue", "fiscalYear": 2013}]


def testDifferentIssueSourceHashCannotMakeCandidateEvidenceClear(auditFixture):
    state, _, sources = auditFixture
    staged = {tuple(row[field] for field in audit.KEY_FIELDS): row for row in state["rows"]}
    issues = [sourceIssue("net_income_mismatch", document_sha256="b" * 64)]

    result = audit.summaryCoverage(audit.readRows(sources), staged, issues)

    assert result["evidenceClearCells"] == 3


@pytest.mark.parametrize("target", ["data/towns/finances/new", "data/towns/executive-summaries/new",
                                    "data/towns/financial-statements/new", "populated"])
def testAuditRefusesCanonicalOrPopulatedOutputBeforeExtraction(auditFixture, target):
    state, _, _ = auditFixture
    if target == "populated":
        path = audit.ROOT / target
        path.mkdir()
        (path / "keep.txt").write_text("keep", encoding="utf-8")

    with pytest.raises(ValueError):
        audit.runAudit(Path(target))

    assert state["called"] == 0


@pytest.mark.parametrize("failure", ["tamper", "extra"])
def testAuditRefusesChangedOrUnexpectedStagedCsvs(auditFixture, failure):
    state, canonical, _ = auditFixture
    before = audit.snapshotCsv(canonical)
    state[failure] = True

    with pytest.raises(ValueError, match="manifest|checksum"):
        audit.runAudit(Path("storage/failed"))

    assert audit.snapshotCsv(canonical) == before
    assert not (audit.ROOT / "storage/failed/comparison.json").exists()
    assert (audit.ROOT / "storage/failed/.audit-running").exists()


def testAuditDetectsBaselineMutationInsteadOfReportingAnUnchangedLedger(auditFixture):
    state, _, _ = auditFixture
    state["mutateBaseline"] = True

    with pytest.raises(RuntimeError, match="Canonical CSVs changed"):
        audit.runAudit(Path("storage/mutated"))

    assert not (audit.ROOT / "storage/mutated/comparison.json").exists()


def testDuplicateKeysCannotSilentlyOverwriteObservations(auditFixture):
    _, canonical, _ = auditFixture
    writeCsv(canonical / "extracted/2015.csv", batch.EXTRACTED_COLUMNS, [figure()])

    with pytest.raises(ValueError, match="Duplicate observation"):
        audit.observations(canonical)


def testDefaultAuditFoldersAreUniqueAndNeverReusePreviousArtifacts(auditFixture):
    first = audit.runAudit()
    second = audit.runAudit()

    assert first["output"] != second["output"]
    assert first["status"] == second["status"] == "completed"


def testComposerRunnerDispatchesAuditToItsReadOnlyModule(tmp_path, monkeypatch):
    path = Path(__file__).resolve().parents[1] / "run.py"
    spec = importlib.util.spec_from_file_location("coaRunnerAuditTest", path)
    runner = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(runner)
    python = tmp_path / "python.exe"
    python.write_text("test executable", encoding="utf-8")
    monkeypatch.setattr(runner, "PYTHON", python)
    monkeypatch.setattr(runner.sys, "argv", ["run.py", "audit", "--output", "storage/audit"])
    calls = []
    monkeypatch.setattr(runner.subprocess, "run", lambda args, **kwargs: calls.append((args, kwargs)) or SimpleNamespace(returncode=7))

    assert runner.main() == 7
    assert calls[0][0] == [python, "-u", "-m", "coa.audit", "--output", "storage/audit"]


@pytest.mark.parametrize("exitCode", [0, 1])
def testComposerTestsRemoveTheirTemporaryFixturesAfterSuccessOrFailure(tmp_path, monkeypatch, exitCode):
    path = Path(__file__).resolve().parents[1] / "run.py"
    spec = importlib.util.spec_from_file_location("coaRunnerCleanupTest", path)
    runner = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(runner)
    python = tmp_path / "python.exe"
    python.write_text("test executable", encoding="utf-8")
    monkeypatch.setattr(runner, "PYTHON", python)
    monkeypatch.setattr(runner, "HERE", tmp_path / "tools/coa")
    monkeypatch.setattr(runner.sys, "argv", ["run.py", "test", "-q"])
    folders = []

    def run(args, **kwargs):
        folder = Path(args[args.index("--basetemp") + 1])
        assert folder.is_relative_to(tmp_path / "storage/app/source-review/coa")
        (folder / "synthetic.json").write_text("{}", encoding="utf-8")
        folders.append(folder)
        return SimpleNamespace(returncode=exitCode)

    monkeypatch.setattr(runner.subprocess, "run", run)
    assert runner.main() == exitCode
    assert folders and not folders[0].exists()


def testCliReportsAuditCompletionSeparatelyFromFinancialApproval(auditFixture, monkeypatch, capsys):
    state, _, _ = auditFixture
    state["issues"] = [sourceIssue("net_income_mismatch", line="performance.net_income")]
    monkeypatch.setattr(batch, "configureLogging", lambda: None)

    audit.main(["--output", "storage/cli"])

    result = json.loads(capsys.readouterr().out)
    assert result["status"] == "needs_review"
    assert result["canonicalUnchanged"] is True
    assert result["requiredSummaryCells"] == result["candidateSummaryCells"] == 4
