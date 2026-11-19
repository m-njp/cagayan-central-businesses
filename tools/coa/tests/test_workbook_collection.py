import hashlib
from io import BytesIO
import json
from pathlib import Path
import stat
import zipfile

from openpyxl import Workbook
import pytest

from coa.collection import Collection, observeDownloads
from coa.workbook_collection import WorkbookCollection, main


@pytest.fixture
def workbookBytes():
    workbook = Workbook()
    workbook.active.append(["Source", 42])
    output = BytesIO()
    workbook.save(output)
    workbook.close()
    return output.getvalue()


def setupCollection(tmpPath, workbookBytes, count=1, members=None):
    downloads = tmpPath / "downloads"
    downloads.mkdir()
    items = []
    for number in range(count):
        name = f"Tuguegarao-City-Annual-Audit-Report-2025-{number}.zip"
        path = downloads / name
        with zipfile.ZipFile(path, "w", zipfile.ZIP_DEFLATED) as archive:
            for member, data in (members if members is not None else [("AAR/FS.xlsx", workbookBytes), ("AAR/report.pdf", b"pdf")]):
                archive.writestr(member, data)
        items.append({"id": str(number), "title": "Tuguegarao City Annual Audit Report 2025",
                      "url": f"https://www.coa.gov.ph/download/9034/{number}/{name}", "fileName": name,
                      "size": path.stat().st_size, "psgcCode": "0201529000", "townFolder": "Tuguegarao-City"})
    inventory = tmpPath / "inventory.json"
    inventory.write_text(json.dumps(items), encoding="utf-8")
    state = tmpPath / "checkpoint.jsonl"
    output = tmpPath / "workbooks"
    return WorkbookCollection(inventory, state, output, 40, 104857600), inventory, state, downloads, output


def completeAll(collection, downloads):
    for fileId, item in collection.items.items():
        collection.completed(fileId, downloads / item["fileName"])


@pytest.mark.parametrize("count", [14, 15, 16])
def testFifteenZipBatchAndPartialFlushAfterRestart(tmp_path, workbookBytes, count):
    collection, inventory, state, downloads, output = setupCollection(tmp_path, workbookBytes, count)
    completeAll(collection, downloads)
    report = collection.export()
    assert report["batchSize"] == 15
    assert len(report["verified"]) == (15 if count >= 15 else 0)
    assert len(report["pending"]) == count % 15
    assert Collection.batchSize == 12
    assert len(list(downloads.glob("*.zip"))) == count % 15
    collection = WorkbookCollection(inventory, state, output, 40, 104857600)
    collection.check()
    assert len(collection.export()["verified"]) == count
    assert not list(downloads.iterdir())
    assert len(list(output.rglob("*.xlsx"))) == count
    assert all(entry["archiveDeleted"] for entry in collection.entries.values())


def testRetainsEveryXlsxWithOriginalBytesAndNestedDuplicateNames(tmp_path, workbookBytes):
    members = [("FS.xlsx", workbookBytes), ("A/FS.xlsx", workbookBytes), ("B/FS.XLSX", workbookBytes),
               ("AAR\\other.xlsx", workbookBytes), ("report.pdf", b"pdf"), ("old.xls", b"xls"),
               ("macros.xlsm", b"xlsm"), ("nested.zip", b"zip")]
    collection, _, state, downloads, output = setupCollection(tmp_path, workbookBytes, members=members)
    with zipfile.ZipFile(downloads / collection.items["0"]["fileName"]) as archive:
        originalNames = {name for name in archive.namelist() if name.lower().endswith(".xlsx")}
    unrelated = downloads / "unrelated.zip"
    unrelated.write_bytes(b"leave alone")
    completeAll(collection, downloads)
    collection.check()
    entry = collection.entries["0"]
    assert len(entry["workbooks"]) == 4
    assert {row["archiveMember"] for row in entry["workbooks"]} == originalNames
    assert all(Path(row["path"]).read_bytes() == workbookBytes for row in entry["workbooks"])
    assert all(row["sha256"] == hashlib.sha256(workbookBytes).hexdigest() for row in entry["workbooks"])
    assert all(path.suffix.lower() == ".xlsx" for path in output.rglob("*") if path.is_file())
    assert unrelated.read_bytes() == b"leave alone"
    events = [json.loads(line) for line in state.read_text().splitlines()]
    assert events[0]["status"] == "pending"
    assert events[1]["status"] == "verified" and not events[1]["archiveDeleted"]
    assert events[2]["archiveDeleted"]


def testReadableArchiveWithoutXlsxIsRecordedThenDeleted(tmp_path, workbookBytes):
    collection, _, _, downloads, output = setupCollection(tmp_path, workbookBytes, members=[("file.pdf", b"pdf")])
    completeAll(collection, downloads)
    collection.check()
    assert collection.entries["0"]["outcome"] == "no_xlsx"
    assert collection.entries["0"]["workbooks"] == []
    assert not list(downloads.iterdir())
    assert not [path for path in output.rglob("*") if path.is_file()]


@pytest.mark.parametrize("name", ["../escape.xlsx", "/absolute.xlsx", "C:/file.xlsx", "A/../../escape.xlsx",
                                   "A\\..\\escape.xlsx", "CON.xlsx", "A/file.xlsx.", "A/file:stream.xlsx"])
def testUnsafePathsAreRejectedWithoutCleanup(tmp_path, workbookBytes, name):
    collection, _, _, downloads, output = setupCollection(tmp_path, workbookBytes, members=[(name, workbookBytes)])
    completeAll(collection, downloads)
    collection.check()
    assert len(collection.export()["failures"]) == 1
    assert list(downloads.glob("*.zip"))
    assert not list(output.rglob("*.xlsx"))


def testSymlinkEntryIsRejected(tmp_path, workbookBytes):
    member = zipfile.ZipInfo("FS.xlsx")
    member.external_attr = (stat.S_IFLNK | 0o777) << 16
    collection, _, _, downloads, _ = setupCollection(tmp_path, workbookBytes, members=[(member, b"target")])
    completeAll(collection, downloads)
    collection.check()
    assert "symbolic link" in collection.entries["0"]["error"]
    assert list(downloads.glob("*.zip"))


@pytest.mark.parametrize("failure", ["invalid_workbook", "corrupt_zip", "duplicate", "members", "bytes", "url", "size", "hash"])
def testFailurePreservesZipAndOtherFilesStillFinish(tmp_path, workbookBytes, failure):
    collection, inventory, state, downloads, output = setupCollection(tmp_path, workbookBytes, count=2)
    path = downloads / collection.items["0"]["fileName"]
    if failure == "invalid_workbook":
        with zipfile.ZipFile(path, "w") as archive:
            archive.writestr("FS.xlsx", b"invalid")
        collection.items["0"]["size"] = path.stat().st_size
    elif failure == "corrupt_zip":
        path.write_bytes(b"x" * path.stat().st_size)
    elif failure == "duplicate":
        with zipfile.ZipFile(path, "w") as archive:
            archive.writestr("FS.xlsx", workbookBytes)
            archive.writestr("fs.xlsx", workbookBytes)
        collection.items["0"]["size"] = path.stat().st_size
    elif failure == "members":
        collection.maxMembers = 1
    elif failure == "bytes":
        collection.maxBytes = 5
    elif failure == "url":
        collection.items["0"]["url"] = "https://evil.test/file.zip"
    elif failure == "size":
        collection.items["0"]["size"] += 1
    elif failure == "hash":
        collection.items["0"]["sha256"] = "0" * 64
    collection.completed("0", path)
    collection.check()
    assert collection.entries["0"]["status"] == "failed"
    assert path.exists()
    collection.maxMembers = 40
    collection.maxBytes = 104857600
    collection.completed("1", downloads / collection.items["1"]["fileName"])
    collection.check()
    assert collection.entries["1"]["status"] == "verified"


def testUnchangedDeletedZipResumesWithoutWorkAndModifiedWorkbookIsNotCached(tmp_path, workbookBytes, monkeypatch):
    collection, inventory, state, downloads, output = setupCollection(tmp_path, workbookBytes)
    completeAll(collection, downloads)
    collection.check()
    before = state.read_bytes()
    collection = WorkbookCollection(inventory, state, output, 40, 104857600)
    monkeypatch.setattr(collection, "validate", lambda _: pytest.fail("Unchanged source checked again"))
    completeAll(collection, downloads)
    collection.check()
    assert state.read_bytes() == before
    Path(collection.entries["0"]["workbooks"][0]["path"]).write_bytes(b"changed")
    completeAll(collection, downloads)
    assert collection.entries["0"]["status"] == "failed"


def testFailedZipRetriesAndChangedArchiveIsCheckedAgain(tmp_path, workbookBytes):
    collection, _, _, downloads, _ = setupCollection(tmp_path, workbookBytes)
    path = downloads / collection.items["0"]["fileName"]
    good = path.read_bytes()
    path.write_bytes(b"invalid")
    completeAll(collection, downloads)
    collection.check()
    assert collection.entries["0"]["status"] == "failed"
    path.write_bytes(good)
    collection.completed("0", path, retryFailed=True)
    collection.check()
    assert collection.entries["0"]["archiveDeleted"]
    path.write_bytes(good + b"changed")
    collection.completed("0", path)
    collection.check()
    assert collection.entries["0"]["status"] == "failed"
    assert path.exists()


def testCheckpointFailureBeforeCleanupPreservesArchiveAndResumeFinishes(tmp_path, workbookBytes, monkeypatch):
    collection, inventory, state, downloads, output = setupCollection(tmp_path, workbookBytes)
    completeAll(collection, downloads)
    original = collection.save

    def interruptedSave(fileId, status, **fields):
        if status == "verified":
            raise OSError("interrupted checkpoint")
        original(fileId, status, **fields)

    monkeypatch.setattr(collection, "save", interruptedSave)
    with pytest.raises(OSError, match="interrupted checkpoint"):
        collection.check()
    assert list(downloads.glob("*.zip"))
    collection = WorkbookCollection(inventory, state, output, 40, 104857600)
    collection.check()
    assert collection.entries["0"]["archiveDeleted"]


def testCleanupFailureRetriesWithoutReextractingAndChangedZipIsPreserved(tmp_path, workbookBytes, monkeypatch):
    collection, inventory, state, downloads, output = setupCollection(tmp_path, workbookBytes)
    completeAll(collection, downloads)
    original = Path.unlink
    with monkeypatch.context() as patch:
        def lockedUnlink(path, *args, **kwargs):
            if path.suffix == ".zip":
                raise PermissionError("Browser file lock")
            return original(path, *args, **kwargs)
        patch.setattr(Path, "unlink", lockedUnlink)
        collection.check()
    assert collection.entries["0"]["status"] == "verified"
    assert "Browser file lock" in collection.entries["0"]["cleanupError"]
    path = downloads / collection.items["0"]["fileName"]
    good = path.read_bytes()
    path.write_bytes(b"changed")
    collection.check()
    assert path.exists()
    assert "changed" in collection.entries["0"]["cleanupError"]
    # Restore the pending deletion's original fingerprint after the simulated change.
    path.write_bytes(good)
    collection.entries["0"]["fingerprint"] = [path.stat().st_size, path.stat().st_mtime_ns, path.stat().st_ctime_ns]
    collection.check()
    assert collection.entries["0"]["archiveDeleted"]


def testWatcherWaitsForStableZipAndIgnoresPartialAndUnrelatedFiles(tmp_path, workbookBytes):
    collection, _, _, downloads, _ = setupCollection(tmp_path, workbookBytes)
    path = downloads / collection.items["0"]["fileName"]
    partial = path.with_name(path.name + ".crdownload")
    partial.write_bytes(b"partial")
    observed = {}
    observeDownloads(collection, downloads, observed)
    assert collection.entries == {}
    partial.unlink()
    assert observeDownloads(collection, downloads, observed)
    assert collection.entries == {}
    observeDownloads(collection, downloads, observed)
    assert collection.entries["0"]["status"] == "pending"
    collection.check()
    observeDownloads(collection, downloads, observed)
    assert collection.entries["0"]["archiveDeleted"]


def testWatcherDoesNotCheckpointDirectZipSaveBeforeEndOfArchiveArrives(tmp_path, workbookBytes):
    collection, _, _, downloads, _ = setupCollection(tmp_path, workbookBytes)
    path = downloads / collection.items["0"]["fileName"]
    original = path.read_bytes()
    path.write_bytes(original[:-22])
    observed = {}
    observeDownloads(collection, downloads, observed)
    observeDownloads(collection, downloads, observed)
    assert collection.entries == {}
    path.write_bytes(original)
    observeDownloads(collection, downloads, observed)
    observeDownloads(collection, downloads, observed)
    assert collection.entries["0"]["status"] == "pending"


def testCliScanFlushAndStatusReuseDeletedSources(tmp_path, workbookBytes, monkeypatch):
    _, inventory, state, downloads, output = setupCollection(tmp_path, workbookBytes)
    base = ["collect-workbooks", "--inventory", str(inventory), "--state", str(state), "--output", str(output),
            "--members", "40", "--bytes", "104857600"]
    monkeypatch.setattr("sys.argv", [*base, "scan", "--downloads", str(downloads)])
    assert main() == 0
    before = state.read_bytes()
    assert main() == 0
    assert before == state.read_bytes()
    monkeypatch.setattr("sys.argv", [*base, "status"])
    assert main() == 0
    manifest = json.loads((state.parent / "checkpoint-manifests/2025/manifest.json").read_text())
    assert manifest[0]["archiveDeleted"]
    assert manifest[0]["url"].startswith("https://www.coa.gov.ph/")
