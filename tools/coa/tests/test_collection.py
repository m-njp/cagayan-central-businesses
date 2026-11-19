import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import time

import pytest

from coa.collection import Collection, collectionLock, fingerprint, main, observeDownloads, validatePdf, watchDownloads, writeJson

ROOT = Path(__file__).resolve().parents[3]
PDF = ROOT / "data/towns/executive-summaries/2024/Tuguegarao-City/Tuguegarao-City-Executive-Summary-2024.pdf"


@pytest.mark.parametrize("lockedAttempts", [2, 6])
def testCheckpointReplacementRetriesTemporaryWindowsLocksAndPreservesOldFile(tmp_path, monkeypatch, lockedAttempts):
    path = tmp_path / "manifest.json"
    path.write_text('{"old": true}', encoding="utf-8")
    replace = Path.replace
    attempts = []
    delays = []

    def lockedReplace(temporary, target):
        attempts.append(target)
        if len(attempts) <= lockedAttempts:
            raise PermissionError("Manifest is temporarily locked")
        return replace(temporary, target)

    monkeypatch.setattr(Path, "replace", lockedReplace)
    monkeypatch.setattr("coa.collection.time.sleep", delays.append)
    if lockedAttempts == 6:
        with pytest.raises(PermissionError):
            writeJson(path, {"new": True})
        assert json.loads(path.read_text()) == {"old": True}
        assert len(attempts) == 6
    else:
        writeJson(path, {"new": True})
        assert json.loads(path.read_text()) == {"new": True}
        assert len(attempts) == 3
    assert len(delays) == min(lockedAttempts, 5)


def setupCollection(tmpPath, count=13):
    downloads = tmpPath / "downloads"
    downloads.mkdir()
    items = []
    for number in range(count):
        name = f"report-{number}.pdf"
        shutil.copyfile(PDF, downloads / name)
        items.append({"id": str(number), "category": "8413",
                      "title": "Tuguegarao City Executive Summary 2024", "fileName": name,
                      "url": f"https://www.coa.gov.ph/download/8413/{number}/{name}",
                      "size": PDF.stat().st_size})
    inventory = tmpPath / "inventory.json"
    inventory.write_text(json.dumps(items), encoding="utf-8")
    state = tmpPath / "checkpoint.jsonl"
    return Collection(inventory, state), inventory, state, downloads


def testTwelfthCompletionValidatesBatchAndFlushChecksRemainderAfterRestart(tmp_path):
    collection, inventory, state, downloads = setupCollection(tmp_path)
    for number in range(11):
        collection.completed(str(number), downloads / f"report-{number}.pdf")
    assert len(collection.pending()) == 11
    assert len(state.read_text().splitlines()) == 11
    collection = Collection(inventory, state)
    collection.completed("11", downloads / "report-11.pdf")
    assert len(collection.export()["verified"]) == 12
    collection.completed("12", downloads / "report-12.pdf")
    collection = Collection(inventory, state)
    assert len(collection.pending()) == 1
    collection.check()
    assert len(collection.export()["verified"]) == 13


def testBadPdfDoesNotStopBatchAndCorrectedDownloadCanBeRetried(tmp_path):
    collection, inventory, state, downloads = setupCollection(tmp_path, 12)
    bad = downloads / "report-2.pdf"
    bad.write_bytes(b"x" * PDF.stat().st_size)
    for number in range(12):
        collection.completed(str(number), downloads / f"report-{number}.pdf")
    report = collection.export()
    assert len(report["verified"]) == 11
    assert report["failures"][0]["id"] == "2"
    assert "header" in report["failures"][0]["error"]
    assert report["failures"][0]["stage"] == "validation"
    shutil.copyfile(PDF, bad)
    collection = Collection(inventory, state)
    collection.completed("2", bad, retryFailed=True)
    collection.check()
    assert len(collection.export()["verified"]) == 12
    assert collection.export()["failures"] == []
    assert '"status": "failed"' in state.read_text()


def testResumeReusesUnchangedFilesAndRevalidatesChangedFiles(tmp_path):
    collection, inventory, state, downloads = setupCollection(tmp_path, 1)
    path = downloads / "report-0.pdf"
    collection.completed("0", path)
    collection.check()
    before = state.read_bytes()
    collection = Collection(inventory, state)
    collection.completed("0", path)
    collection.check()
    assert state.read_bytes() == before
    path.write_bytes(b"bad")
    collection.completed("0", path)
    collection.check()
    assert len(collection.export()["failures"]) == 1


def testTornCheckpointTailIsRecoveredWithoutLosingCompletedFiles(tmp_path):
    collection, inventory, state, downloads = setupCollection(tmp_path, 2)
    collection.completed("0", downloads / "report-0.pdf")
    with state.open("ab") as handle:
        handle.write(b'{"id":')
    collection = Collection(inventory, state)
    collection.completed("1", downloads / "report-1.pdf")
    collection.check()
    assert len(collection.export()["verified"]) == 2
    for line in state.read_text().splitlines():
        json.loads(line)


def testFailedBrowserDownloadIsDurableAndDoesNotHideOtherPendingFiles(tmp_path):
    collection, inventory, state, downloads = setupCollection(tmp_path, 2)
    collection.failed("0", "Browser download timed out")
    collection.completed("1", downloads / "report-1.pdf")
    collection = Collection(inventory, state)
    collection.check()
    report = collection.export()
    assert len(report["verified"]) == 1
    assert report["failures"][0]["stage"] == "download"
    assert report["failures"][0]["error"] == "Browser download timed out"


@pytest.mark.parametrize("change", [
    {"url": "https://coa.gov.ph.evil.test/file.pdf"},
    {"title": "Tuguegarao City Annual Audit Report 2024"},
    {"sha256": "0" * 64},
])
def testInvalidSourceOrExpectedHashIsRecordedIndividually(tmp_path, change):
    collection, inventory, state, downloads = setupCollection(tmp_path, 2)
    collection.items["0"].update(change)
    for number in range(2):
        collection.completed(str(number), downloads / f"report-{number}.pdf")
    collection.check()
    assert len(collection.export()["failures"]) == 1
    assert len(collection.export()["verified"]) == 1


def testScanCliRecordsMissingFileAndChecksFinalBatch(tmp_path, monkeypatch):
    _, inventory, state, downloads = setupCollection(tmp_path, 2)
    (downloads / "report-0.pdf").unlink()
    monkeypatch.setattr("sys.argv", ["collect", "--inventory", str(inventory), "--state", str(state),
                                    "scan", "--downloads", str(downloads)])
    assert main() == 1
    report = json.loads(state.with_suffix(".report.json").read_text())
    assert len(report["failures"]) == 1
    assert len(report["verified"]) == 1


def testCollectionRejectsConcurrentWriterAndReleasesLock(tmp_path):
    state = tmp_path / "checkpoint.jsonl"
    with collectionLock(state):
        with pytest.raises(OSError):
            with collectionLock(state):
                pass
    with collectionLock(state):
        pass


def testManifestsKeepFullInventoryButOnlyIncludeVerifiedDownloads(tmp_path):
    collection, inventory, state, downloads = setupCollection(tmp_path, 2)
    collection.completed("0", downloads / "report-0.pdf")
    collection.failed("1", "Timed out")
    collection.check()
    folder = collection.manifestRoot / "2024"
    assert len(json.loads((folder / "inventory.json").read_text())) == 2
    manifest = json.loads((folder / "manifest.json").read_text())
    assert [row["id"] for row in manifest] == ["0"]
    assert manifest[0]["fiscalYear"] == 2024
    assert manifest[0]["pages"] > 0
    assert manifest[0]["downloadPath"] == str((downloads / "report-0.pdf").resolve())


def testRetryAndForcedRecheckBypassCacheOnlyWhenRequested(tmp_path):
    collection, inventory, state, downloads = setupCollection(tmp_path, 1)
    path = downloads / "report-0.pdf"
    collection.items["0"]["sha256"] = "0" * 64
    collection.completed("0", path)
    collection.check()
    before = state.read_bytes()
    collection.completed("0", path)
    assert state.read_bytes() == before
    collection.completed("0", path, retryFailed=True)
    assert len(collection.pending()) == 1
    collection.items["0"].pop("sha256")
    collection.completed("0", path)
    collection.check()
    before = state.read_bytes()
    collection.completed("0", path, recheck=True)
    collection.check()
    assert state.read_bytes() != before
    assert len(collection.export()["verified"]) == 1


@pytest.mark.parametrize("path", sorted((ROOT / "data/towns/executive-summaries").rglob("*.pdf")), ids=lambda path: path.name)
def testStoredCoaPdfStructuresAreAccepted(path):
    result = validatePdf({"downloadPath": str(path), "fingerprint": fingerprint(path),
                          "item": {"url": "https://www.coa.gov.ph/download/example.pdf",
                                   "title": "Stored Executive Summary 2024", "size": path.stat().st_size}})
    assert result["pdfVerified"]
    assert result["pages"] > 0


def testComposerRunnerResolvesCollectionPathsFromRepositoryRoot(tmp_path):
    _, inventory, state, downloads = setupCollection(tmp_path, 1)
    result = subprocess.run([sys.executable, str(ROOT / "tools/coa/run.py"), "collect",
                             "--inventory", os.path.relpath(inventory, ROOT),
                             "--state", os.path.relpath(state, ROOT),
                             "scan", "--downloads", os.path.relpath(downloads, ROOT)],
                            cwd=ROOT, capture_output=True, text=True, timeout=30)
    assert result.returncode == 0, result.stderr
    assert json.loads(result.stdout)["verified"] == 1


@pytest.mark.parametrize("invalid", [{}, [{"id": "0"}], [
    {"id": "0", "title": "Report", "url": "https://www.coa.gov.ph/report.pdf", "fileName": "report.pdf", "size": None},
]])
def testMalformedInventoryReturnsCommandErrorWithoutChangingCheckpoint(tmp_path, monkeypatch, invalid):
    _, inventory, state, downloads = setupCollection(tmp_path, 1)
    inventory.write_text(json.dumps(invalid), encoding="utf-8")
    monkeypatch.setattr("sys.argv", ["collect", "--inventory", str(inventory), "--state", str(state), "status"])
    assert main() == 2
    assert not state.exists()


def testBrowserFolderObserverCheckpointsEachStableSaveAndChecksTheTwelfth(tmp_path):
    collection, inventory, state, downloads = setupCollection(tmp_path, 13)
    observed = {}
    for number in range(13):
        (downloads / f"report-{number}.pdf").rename(downloads / f"report-{number}.pdf.crdownload")
    for number in range(12):
        path = downloads / f"report-{number}.pdf"
        path.with_suffix(".pdf.crdownload").rename(path)
        observeDownloads(collection, downloads, observed)
        observeDownloads(collection, downloads, observed)
        assert str(number) in Collection(inventory, state).entries
    assert len(collection.export()["verified"]) == 12
    assert "12" not in collection.entries


def testObserverIgnoresActivePartialAndHandlesBadPdfWithoutStopping(tmp_path):
    collection, inventory, state, downloads = setupCollection(tmp_path, 3)
    partial = downloads / "report-0.pdf.crdownload"
    partial.write_bytes(b"still downloading")
    (downloads / "report-1.pdf").write_bytes(b"corrupt")
    observed = {}
    observeDownloads(collection, downloads, observed)
    observeDownloads(collection, downloads, observed)
    assert "0" not in collection.entries
    assert len(collection.pending()) == 2
    collection.check()
    assert [entry["id"] for entry in collection.export()["failures"]] == ["1"]
    assert [entry["id"] for entry in collection.export()["verified"]] == ["2"]


def testWatcherFinishMarkerFlushesRemainderAndRecordsMissingDownloads(tmp_path, monkeypatch):
    collection, inventory, state, downloads = setupCollection(tmp_path, 2)
    (downloads / "report-1.pdf").unlink()
    finish = tmp_path / "finished.flag"
    monkeypatch.setattr("coa.collection.time.sleep", lambda interval: finish.touch())
    watchDownloads(collection, downloads, finish)
    report = collection.export()
    assert len(report["verified"]) == 1
    assert report["failures"][0]["id"] == "1"
    assert "No completed PDF" in report["failures"][0]["error"]


def testWatcherIdleTimeoutPreservesCompletedFilesAndRecordsMissingOnes(tmp_path, monkeypatch):
    collection, inventory, state, downloads = setupCollection(tmp_path, 2)
    (downloads / "report-1.pdf").unlink()
    ticks = iter([0, 0, 0, 0, 5])
    monkeypatch.setattr("coa.collection.time.monotonic", lambda: next(ticks))
    monkeypatch.setattr("coa.collection.time.sleep", lambda interval: None)
    watchDownloads(collection, downloads, idleSeconds=1)
    assert len(collection.export()["verified"]) == 1
    assert len(collection.export()["failures"]) == 1


def testWatcherRejectsAnOldFinishMarker(tmp_path):
    collection, inventory, state, downloads = setupCollection(tmp_path, 1)
    finish = tmp_path / "finished.flag"
    finish.touch()
    with pytest.raises(ValueError, match="fresh finish marker"):
        watchDownloads(collection, downloads, finish)


def testBrowserSavesAreAutomaticallyCheckpointedAndValidatedByWatcherProcess(tmp_path):
    _, inventory, state, downloads = setupCollection(tmp_path, 13)
    for path in downloads.glob("*.pdf"):
        path.unlink()
    finish = tmp_path / "browser-finished.flag"
    command = [sys.executable, str(ROOT / "tools/coa/run.py"), "collect",
               "--inventory", str(inventory), "--state", str(state), "watch",
               "--downloads", str(downloads), "--finish-file", str(finish),
               "--poll-seconds", "0.05", "--idle-seconds", "30"]
    with subprocess.Popen(command, cwd=ROOT, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True) as process:
        try:
            for number in range(13):
                path = downloads / f"report-{number}.pdf"
                if number == 12:
                    path.write_bytes(b"corrupt browser save")
                else:
                    shutil.copyfile(PDF, path)
                deadline = time.monotonic() + 10
                while True:
                    if state.exists() and f'"id": "{number}"' in state.read_text():
                        break
                    if process.poll() is not None or time.monotonic() >= deadline:
                        pytest.fail("Watcher did not automatically checkpoint the browser save")
                    time.sleep(0.02)
            finish.touch()
            stdout, stderr = process.communicate(timeout=30)
            assert process.returncode == 1, stderr
        finally:
            if process.poll() is None:
                process.kill()
                process.communicate()
    report = json.loads(state.with_suffix(".report.json").read_text())
    assert len(report["verified"]) == 12
    assert [entry["id"] for entry in report["failures"]] == ["12"]
    assert report["pending"] == []


def testRoundedCoaSizeIsRecordedSeparatelyFromExactByteVerification(tmp_path):
    collection, inventory, state, downloads = setupCollection(tmp_path, 1)
    item = collection.items["0"]
    item.pop("size")
    item["sizeLabel"] = f"{PDF.stat().st_size / 1024:.0f} KB"
    collection.completed("0", downloads / "report-0.pdf")
    collection.check()
    verified = collection.export()["verified"][0]
    assert verified["listedSizeCheck"] == "rounded_display"
    assert verified["bytes"] == PDF.stat().st_size


def testRoundedSizeMismatchIsAnIndividualFailure(tmp_path):
    collection, inventory, state, downloads = setupCollection(tmp_path, 1)
    collection.items["0"].pop("size")
    collection.items["0"]["sizeLabel"] = "1 KB"
    collection.completed("0", downloads / "report-0.pdf")
    collection.check()
    assert "byte size" in collection.export()["failures"][0]["error"]


def testBrowserFilenameMatchingHandlesAccentsPunctuationAndRepeatedSpaces(tmp_path):
    collection, inventory, state, downloads = setupCollection(tmp_path, 1)
    collection.items["0"]["fileName"] = "Sto. Niño  Executive Summary 2017.pdf"
    path = downloads / "Sto.-Nino-Executive-Summary-2017.pdf"
    (downloads / "report-0.pdf").rename(path)
    observed = {}
    observeDownloads(collection, downloads, observed)
    observeDownloads(collection, downloads, observed)
    collection.check()
    assert collection.export()["verified"][0]["downloadPath"] == str(path.resolve())


def testAmbiguousNormalizedFilenamesAreNotGuessed(tmp_path):
    collection, inventory, state, downloads = setupCollection(tmp_path, 1)
    collection.items["0"]["fileName"] = "Peña Executive Summary 2017.pdf"
    path = downloads / "Pena-Executive-Summary-2017.pdf"
    (downloads / "report-0.pdf").rename(path)
    shutil.copyfile(PDF, downloads / "Pe-na-Executive-Summary-2017.pdf")
    observed = {}
    observeDownloads(collection, downloads, observed)
    observeDownloads(collection, downloads, observed)
    assert collection.entries == {}
