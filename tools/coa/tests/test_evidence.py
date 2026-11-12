"""Financial evidence must survive adversarial changes, not merely repeat digits on a page."""

import csv
import hashlib
from decimal import Decimal
from pathlib import Path

import pytest

from coa import __main__ as batch
from coa import summary
from coa.checks import documentChecks
from coa.intake import scan
from coa.lines import load
from coa.summary import Figure, Result, confirmDigits, documentContext, missingCore, parseSummary, parseText

ROOT = Path(__file__).resolve().parents[3]
LINES, ALIASES = load(ROOT / "data/towns/finances/lines.csv")


def parseRows(*rows, year=2025, page=1):
    return parseText([(page, "Financial Highlights"), (page, f"{year} {year - 1}"),
                      *((page, row) for row in rows)], year, LINES, ALIASES)


def values(result):
    return {(figure.code, figure.fiscalYear): figure.value for figure in result.figures}


def kinds(result):
    return {issue.kind for issue in result.issues}


@pytest.mark.parametrize("token", [
    "(123.45", "123.45)", "1,23.45", "P=(123.45", "(P     123.45",
    "(₱     123.45", "12.345", "1e3", "123.45%", "$123.45",
])
def testMalformedMainCellsCannotBeSalvagedFromInnerNumericSubstrings(token):
    result = parseRows(f"Income {token} 90.00 10.00", page=3)

    assert not any(figure.code == "performance.revenue" for figure in result.figures)
    assert any(issue.kind == "malformed_amount" and issue.code == "performance.revenue" and issue.page == 3
               for issue in result.issues)


@pytest.mark.parametrize(("candidate", "reference"), [
    (Figure("performance.revenue", 2025, Decimal("-100.00"), None, "Income", 1),
     "Income 100.00 90.00 10.00"),
    (Figure("performance.revenue", 2025, Decimal("100.00"), None, "Income", 1),
     "Expenses 100.00 90.00 10.00"),
    (Figure("performance.revenue", 2025, Decimal("90.00"), None, "Income", 1),
     "Income 100.00 90.00 10.00"),
    (Figure("performance.revenue", 2025, Decimal("100.00"), "includes_subsidy_from_other_funds", "Income", 1),
     "Income 100.00 90.00 10.00"),
    (Figure("performance.revenue", 2025, Decimal("100.00"), None, "Income", 2),
     "Income 100.00 90.00 10.00"),
])
def testIndependentConfirmationBindsSignRowYearDefinitionAndPage(candidate, reference):
    pages = [(1, "Financial Highlights\n2025 2024\n" + reference)]
    result = confirmDigits(Result([candidate], [], {}), Path("unused.pdf"), "pdfium",
                           lambda engine: pages, 2025, LINES, ALIASES)

    assert any(issue.kind == "engine_disagreement" and issue.code == "performance.revenue"
               for issue in result.issues)


def testMatchingSignedRowWithMatchingFiscalColumnIsCorroborated():
    candidate = Figure("performance.revenue", 2025, Decimal("-100.00"), None, "Income", 1)
    pages = [(1, "Financial Highlights\n2025 2024\nIncome (100.00) 90.00 (190.00)")]
    result = confirmDigits(Result([candidate], [], {}), Path("unused.pdf"), "pdfium",
                           lambda engine: pages, 2025, LINES, ALIASES)

    assert "engine_disagreement" not in kinds(result)
    assert any(item["raw"] == "Income (100.00) 90.00 (190.00)" and item["engine"] == "pdfium"
               for item in result.evidence)


def testConflictingSectionYearsDoNotReuseTheFirstHeaderForLaterIncome():
    result = parseRows("Assets 100.00 90.00 10.00", "2024 2023", "Income 80.00 70.00 10.00")

    assert "header_year_context_changed" in kinds(result)
    assert not any(figure.code == "performance.revenue" for figure in result.figures)
    assert values(result)[("position.assets", 2025)] == Decimal("100.00")


def testReversedButValidSectionHeadersRetainEachRowsPrintedYearOrder():
    result = parseRows("Assets 100.00 90.00 10.00", "2024 2025", "Income 80.00 100.00 20.00")

    assert values(result)[("position.assets", 2025)] == Decimal("100.00")
    assert values(result)[("performance.revenue", 2025)] == Decimal("100.00")
    assert values(result)[("performance.revenue", 2024)] == Decimal("80.00")
    assert "column_order_changed" in kinds(result)
    assert "header_year_context_changed" not in kinds(result)


def testDuplicateRowsWithReversedColumnsCompareFiscalValuesRatherThanRawOrder():
    result = parseRows("Income 100.00 90.00 10.00", "2024 2025", "Income 90.00 100.00 10.00")

    assert values(result)[("performance.revenue", 2025)] == Decimal("100.00")
    assert "conflicting_values" not in kinds(result)


@pytest.mark.parametrize(("header", "issueKind"), [(None, "header_years_missing"), ("2025 2025", "header_years_repeated")])
def testAmbiguousYearsRemainHardErrorsInsteadOfSilentCandidateAssignments(header, issueKind):
    rows = [(2, "Financial Highlights")]
    if header is not None:
        rows.append((2, header))
    rows.append((2, "Income 100.00 90.00 10.00"))
    result = parseText(rows, 2025, LINES, ALIASES)

    assert any(issue.kind == issueKind and issue.severity == "error" and issue.page == 2 for issue in result.issues)


@pytest.mark.parametrize("before", [[], ["Assets 100.00 90.00 10.00"]])
def testAuditSectionCannotSupplyMissingFinancialRows(before):
    result = parseRows(*before, "Independent Auditor's Report", "Income 100.00 90.00 10.00")

    assert not any(figure.code == "performance.revenue" for figure in result.figures)


@pytest.mark.parametrize(("heading", "issueKind"), [
    ("Amounts in thousands of pesos", "unsupported_units"),
    ("Figures are expressed in millions", "unsupported_units"),
    ("Amounts in USD", "unsupported_units"),
    ("General Fund", "unsupported_fund_scope"),
    ("Special Education Fund", "unsupported_fund_scope"),
])
def testUnsupportedScaleCurrencyOrFundDoesNotBecomeConsolidatedPesos(heading, issueKind):
    result = parseRows(heading, "Income 100.00 90.00 10.00")

    assert result.figures == []
    assert issueKind in kinds(result)
    assert any(item["raw"] == "Income 100.00 90.00 10.00" for item in result.evidence)


def testPrintedDashIsMissingEvidenceRatherThanZeroOrNegativeNextCell():
    result = parseRows("Assets - 90.00 10.00", "Liabilities 40.00 30.00 10.00",
                       "Government Equity 60.00 60.00 0.00", "Income 100.00 90.00 10.00",
                       "Expenses 80.00 70.00 10.00")

    assert ("position.assets", 2025) not in values(result)
    assert values(result)[("position.assets", 2024)] == Decimal("90.00")
    assert missingCore(result, 2025) == ["position.assets 2025"]
    assert any(issue.kind == "value_not_reported" and issue.code == "position.assets"
               for issue in result.issues)


def testFallbackCompletenessRequiresCurrentCellsAndStillNeedsIndependentConfirmation(monkeypatch):
    text = "\n".join(["Financial Highlights", "2025 2024", "Assets 100.00 90.00 10.00",
                      "Liabilities 40.00 30.00 10.00", "Government Equity 60.00 60.00 0.00",
                      "Income 100.00 90.00 10.00", "Expenses 80.00 70.00 10.00"])
    calls = []

    def readers(path, engine):
        calls.append(engine)
        return [(1, text.replace("Assets 100.00", "Assets -") if engine == "pdfplumber" else text)]

    monkeypatch.setattr(summary, "readPages", readers)
    result = parseSummary(Path("unused.pdf"), 2025, LINES, ALIASES)

    assert values(result)[("position.assets", 2025)] == Decimal("100.00")
    assert "read_with_fallback_engine" in kinds(result)
    assert any(issue.kind == "engine_disagreement" and issue.code == "position.assets" for issue in result.issues)
    assert calls.count("pdfplumber") == calls.count("pdfium") == 1


def testTextlessReportStatesOcrOrManualReviewRequirement(monkeypatch):
    monkeypatch.setattr(summary, "readPages", lambda path, engine: [(1, "")])
    result = parseSummary(Path("unused.pdf"), 2025, LINES, ALIASES)

    assert result.figures == []
    assert "no_text_layer" in kinds(result)


@pytest.mark.parametrize(("printed", "expected"), [
    ("Municipality of Sta. Praxedes, Cagayan", "Santa Praxedes"),
    ("Municipality of Sto. Niño, Cagayan", "Santo Niño"),
    ("Municipality of Peñablanca, Cagayan", "Peñablanca"),
    ("Municipality of Lal-lo, Cagayan", "Lal-Lo"),
    ("City of Tuguegarao, Cagayan", "Tuguegarao City"),
])
def testKnownGovernmentNameVariantsMatchExpectedMunicipality(printed, expected):
    assert documentContext([(1, printed + "\nExecutive Summary")], expected) == []


def testWrongMunicipalityWithCorrectYearDoesNotProduceImportableFigures(monkeypatch):
    text = "Municipality of Abulug, Cagayan\nExecutive Summary\nFinancial Highlights\n2025 2024\nIncome 100.00 90.00 10.00"
    monkeypatch.setattr(summary, "readPages", lambda path, engine: [(1, text)])
    result = parseSummary(Path("unused.pdf"), 2025, LINES, ALIASES, expectedTown="Claveria")

    assert result.figures == []
    assert "municipality_mismatch" in kinds(result)


def testLongerDifferentGovernmentNameCannotMatchOnlyTheExpectedNamePrefix():
    issues = documentContext([(1, "Municipality of Abulugville, Cagayan\nExecutive Summary")], "Abulug")

    assert any(issue.kind == "municipality_mismatch" for issue in issues)


def testMissingGovernmentIdentityCannotBeTreatedAsConfirmed(monkeypatch):
    text = "Executive Summary\nFinancial Highlights\n2025 2024\nIncome 100.00 90.00 10.00"
    monkeypatch.setattr(summary, "readPages", lambda path, engine: [(1, text)])
    result = parseSummary(Path("unused.pdf"), 2025, LINES, ALIASES, expectedTown="Abulug")

    assert result.figures
    assert any(issue.kind == 'municipality_unconfirmed' and issue.severity == 'warning' for issue in result.issues)


def testNeighboringProvinceDoesNotBecomeTheReportsJurisdiction():
    issues = documentContext([(1, 'Municipality of Solana was founded in 1952. It is bounded on the southwest by the Province of Kalinga.\nExecutive Summary')], 'Solana', 'Cagayan')
    assert not any(issue.kind == 'province_mismatch' for issue in issues)
    assert any(issue.kind == 'province_unconfirmed' and issue.severity == 'warning' for issue in issues)


@pytest.mark.parametrize('relative,reportYear,code,fiscalYear,expected', [
    ('2015/Camalaniugan/Camalaniugan-Executive-Summary-2015.pdf', 2015, 'performance.revenue', 2014, '67494453.45'),
    ('2019/Sto.-Nino/Sto.-Nino-Executive-Summary-2019.pdf', 2019, 'budget.appropriations_current', 2018, '143382611.80'),
])
def testCorpusCorrectionsKeepPrintedFiscalColumnsAndAppropriationScope(relative, reportYear, code, fiscalYear, expected):
    path = Path(__file__).resolve().parents[3] / 'data/towns/executive-summaries' / relative
    result = parseSummary(path, reportYear, LINES, ALIASES)
    figure = next(item for item in result.figures if item.code == code and item.fiscalYear == fiscalYear)
    assert str(figure.value) == expected
    assert figure.page > 0


def testCandidateEvidenceRetainsSignedRawTokenAndPrintedYears():
    result = parseRows("Income (100.00) 90.00 (190.00)", page=4)
    evidence = next(item for item in result.evidence if item["line_code"] == "performance.revenue")

    assert evidence["page"] == 4
    assert evidence["tokens"][0] == "(100.00)"
    assert evidence["printed_years"] == [2025, 2024]
    assert values(result)[("performance.revenue", 2025)] == Decimal("-100.00")


@pytest.mark.parametrize("town", ["Amulung", "Rizal"])
def testRealUploadReceivesTheSameArithmeticFindingsAsBatch(town, tmp_path):
    source = ROOT / f"data/towns/executive-summaries/2025/{town}/{town}-Executive-Summary-2025.pdf"
    parsed = parseSummary(source, 2025, LINES, ALIASES)
    expected = {(issue.kind, issue.code, issue.message) for issue in documentChecks(parsed.figures, parsed.changes, 2025)}

    uploaded = scan(source, "executive_summary", 2025, tmp_path, 40, 104857600)
    actual = {(issue["kind"], issue["code"], issue["message"]) for issue in uploaded["issues"]}

    assert expected <= actual
    assert any(issue["kind"] == "net_income_mismatch" for issue in uploaded["issues"])
    assert all(issue["document_kind"] == "executive_summary" for issue in uploaded["issues"])
    assert all(issue["document_sha256"] == hashlib.sha256(source.read_bytes()).hexdigest()
               for issue in uploaded["issues"])


def testChecksumMismatchNeverParsesOrExportsFiguresWithTheExpectedOldHash(tmp_path, monkeypatch):
    sources = tmp_path / "summaries"
    file = sources / "2025/Abulug/report.pdf"
    file.parent.mkdir(parents=True)
    file.write_bytes(b"changed source")
    source = {"psgc_code": "0201501000", "town": "Abulug", "year": "2025", "file": "2025/Abulug/report.pdf",
              "sha256": "a" * 64, "source_url": "https://www.coa.gov.ph/report.pdf", "retrieved_on": "2026-11-12"}
    monkeypatch.setattr(batch, "SUMMARIES", sources)
    monkeypatch.setattr(batch, "readCsv", lambda path: [source] if path == sources / "sources.csv" else [])

    def mustNotParse(*args, **kwargs):
        pytest.fail("A source with a failed checksum must not reach the reader")

    monkeypatch.setattr(batch, "parseSummary", mustNotParse)
    output = tmp_path / "staged"
    batch.extract(output)

    with (output / "extracted/2025.csv").open(encoding="utf-8", newline="") as extracted:
        assert list(csv.DictReader(extracted)) == []
    with (output / "issues/2025.csv").open(encoding="utf-8", newline="") as issues:
        failure = next(csv.DictReader(issues))
    assert failure["kind"] == "checksum_mismatch"
    assert failure["document_kind"] == "executive_summary"
    assert failure["document_sha256"] == source["sha256"]


def testProvinceHeaderAboveGovernmentNameConfirmsTheJurisdiction():
    assert documentContext([(1, "Province of Cagayan\nMunicipality of Rizal\nExecutive Summary")], "Rizal", "Cagayan") == []


def testSameNamedGovernmentInAnotherProvinceIsWithheld(monkeypatch):
    text = "Province of Nueva Ecija\nMunicipality of Rizal\nExecutive Summary\nFinancial Highlights\n2025 2024\nIncome 100.00 90.00 10.00"
    monkeypatch.setattr(summary, "readPages", lambda path, engine: [(1, text)])
    result = parseSummary(Path('unused.pdf'), 2025, LINES, ALIASES, expectedTown='Rizal', expectedProvince='Cagayan')
    assert result.figures == []
    assert 'province_mismatch' in kinds(result)


def testNeighborMentionCannotReplaceTheGovernmentWhoseAccountsWereAudited():
    text = "Municipality of Abulug, Cagayan is bounded by the municipality of Claveria. The audit covered the accounts and operations of the Municipality of Abulug, Cagayan."
    issues = documentContext([(1, text)], 'Claveria', 'Cagayan')
    assert any(issue.kind == 'municipality_mismatch' for issue in issues)


def testCoverPageDoesNotHideTheGovernmentHeaderOnTheNextPage():
    pages = [(1, 'A. Executive Summary'), (2, 'Municipality of Abulug, Cagayan\nFinancial Highlights')]
    assert documentContext(pages, 'Abulug', 'Cagayan') == []


@pytest.mark.parametrize('heading', ['Amounts in thousands', "Figures in PHP'000", 'Amounts in P(000)', 'Values expressed in millions', 'Amounts in USD'])
def testUnsupportedScaleVariantsNeverBecomePesoFigures(heading):
    result = parseRows(heading, 'Income 100.00 90.00 10.00')
    assert result.figures == []
    assert 'unsupported_units' in kinds(result)


def testArithmeticFindingsRetainTheActualPageAndFiscalYear():
    result = parseRows('Assets 100.00 80.00 20.00', 'Liabilities 40.00 40.00 0.00', 'Government Equity 40.00 40.00 0.00', page=3)
    issue = next(issue for issue in documentChecks(result.figures, result.changes, 2025) if issue.kind == 'balance_mismatch')
    assert issue.page == 3
    assert issue.fiscalYear == 2025


def summaryAt(relative):
    return ROOT / 'data/towns/executive-summaries' / relative


@pytest.mark.parametrize('relative,reportYear,code,fiscalYear,expected', [
    # A "Financial Highlights" line right before "Operational Highlights" on page 1; the table follows on page 2.
    ('2014/Lal-lo/Lal-Lo-Executive-Summary-2014.pdf', 2014, 'performance.revenue', 2014, '149256835.22'),
    # A misprinted Obligations cell must not swap in a reader that cannot see page 2.
    ('2018/Alcala/Alcala-Executive-Summary-2018.pdf', 2018, 'budget.appropriations_current', 2018, '139073265.84'),
    # pdfium splits the page differently; the amounts are still printed on a Liabilities line.
    ('2020/Gattaran/Gattaran-Executive-Summary-2020.pdf', 2020, 'position.liabilities', 2020, '82006419.87'),
    # "Allotments – 58,465,219.29" with "Continuing" below: the en dash is label punctuation.
    ('2022/Penablanca/Penablanca-Executive-Summary-2022.pdf', 2022, 'budget.allotments_continuing', 2022, '58465219.29'),
    # "Total Appropriations - 59,825,645.91" is Continuing 2019, never a not-reported cell followed by 2018.
    ('2019/Sto.-Nino/Sto.-Nino-Executive-Summary-2019.pdf', 2019, 'budget.appropriations_continuing', 2019, '59825645.91'),
])
def testHistoricalLayoutsReadThePrintedCell(relative, reportYear, code, fiscalYear, expected):
    result = parseSummary(summaryAt(relative), reportYear, LINES, ALIASES)
    figure = next(item for item in result.figures if item.code == code and item.fiscalYear == fiscalYear)
    assert str(figure.value) == expected
    assert not any(issue.kind == 'engine_disagreement' and issue.code == code for issue in result.issues)


def testGattaran2020IsConfirmedByBothReadersWithoutRebuildingPdfiumRows():
    result = parseSummary(summaryAt('2020/Gattaran/Gattaran-Executive-Summary-2020.pdf'), 2020, LINES, ALIASES)
    assert 'engine_disagreement' not in kinds(result)
    assert 'read_with_fallback_engine' not in kinds(result)


@pytest.mark.parametrize('relative,reportYear,code,printed', [
    ('2014/Sta.-Teresita/Santa-Teresita-Executive-Summary-2014.pdf', 2014, 'position.liabilities', '17,530,755,09'),
    ('2018/Alcala/Alcala-Executive-Summary-2018.pdf', 2018, 'budget.obligations_current', '101,724,726,81'),
])
def testMisprintedSourceAmountsAreWithheldAsTheReportsOwn(relative, reportYear, code, printed):
    result = parseSummary(summaryAt(relative), reportYear, LINES, ALIASES)
    issue = next(item for item in result.issues if item.code == code and item.kind == 'malformed_amount')
    assert printed in issue.message
    assert summary.family(issue.kind) == 'report'


def testSplitRowIsTheParsersLimitationNotAMisprint():
    pages = summary.readPages(summaryAt('2022/Alcala/Alcala-Executive-Summary-2022.pdf'), 'pdfplumber')
    result = parseText([(number, line.strip()) for number, text in pages for line in text.splitlines() if line.strip()], 2022, LINES, ALIASES)
    assert any(issue.kind == 'incomplete_row' and issue.code == 'budget.appropriations_current' for issue in result.issues)
    assert not any(issue.kind == 'malformed_amount' and issue.code == 'budget.appropriations_current' for issue in result.issues)
    assert summary.family('incomplete_row') == 'reading'


@pytest.mark.parametrize('relative,town,other', [
    ('2017/Lasam/Lasam-Executive-Summary-2017.pdf', 'Lasam', 'Abulug'),
    ('2017/Pamplona/Pamplona-Executive-Summary-2017.pdf', 'Pamplona', 'Sanchez Mira'),
])
def testAuditScopeSentenceNamingAnotherTownIsFlaggedWithoutWithholdingTheReport(relative, town, other):
    result = parseSummary(summaryAt(relative), 2017, LINES, ALIASES, expectedTown=town, expectedProvince='Cagayan')
    conflict = next(issue for issue in result.issues if issue.kind == 'identity_text_conflict')
    assert conflict.severity == 'warning' and other in conflict.message
    assert 'municipality_mismatch' not in kinds(result)
    assert any(figure.code == 'performance.revenue' and figure.fiscalYear == 2017 for figure in result.figures)


@pytest.mark.parametrize(('line', 'misprinted'), [
    ('Income 48,014,545,75 40,000,000.00 8,014,545.75', True),
    ('Allotments – Current 347,374.045.73 329,880,040.98 17,494,004.75', True),
    ('Income (123.45 90.00 10.00', True),
    ('Appropriations- 2,635,072.02', False),
    ('Allotments – 58,465,219.29 39,156,928.18 19,308,291.11 49.31%', False),
])
def testMisprintedAmountDetectionSeparatesSourceFromLayout(line, misprinted):
    from coa.numbers import hasMisprintedAmount
    assert hasMisprintedAmount(line) is misprinted


def testOneDecimalAmountsAreReadWithoutAFinding():
    # Solana 2022 prints 269,208,843.1 for .10; the owner decided on 26-11-12 that this is not a finding.
    result = parseRows("Income 73,876,848.9 80,326,615.65 6,449,766.66")
    assert values(result)[("performance.revenue", 2025)] == Decimal("73876848.9")
    assert "malformed_amount" not in kinds(result) and "one_decimal_amount" not in kinds(result)


@pytest.mark.parametrize(("printed", "other", "joined"), [
    ("Income P 1 41,203,192.33 P 1 25,237,123.23 P 15,966,069.10", "141,203,192.33 125,237,123.23",
     "Income P 141,203,192.33 P 125,237,123.23 P 15,966,069.10"),
    ("Government Equity 9 9,178,004.35 9 8,087,855.49 1,090,148.86", "99,178,004.35 98,087,855.49",
     "Government Equity 99,178,004.35 98,087,855.49 1,090,148.86"),
    ("Note 2 1,234.56 3", "Note 2 1,234.56 3", "Note 2 1,234.56 3"),
    ("Income P 1 41,203,192.33 P 1 25,237,123.23 P 15,966,069.10", "41,203,192.33", "Income P 1 41,203,192.33 P 1 25,237,123.23 P 15,966,069.10"),
    # Aparri 2015 drops its 2015 cell: joining would turn the 2014 amount into 2015 and the change into 2014.
    ("Liabilities 2 5,117,390.91 36,443,935.62 145.09%", "25,117,390.91 36,443,935.62", "Liabilities 2 5,117,390.91 36,443,935.62 145.09%"),
])
def testDigitsSpacedOffAreJoinedOnlyWhereTheOtherReaderPrintsTheJoinedAmount(printed, other, joined):
    from coa.numbers import joinSplitDigits
    assert joinSplitDigits(printed, other) == joined


def testSolana2014KerningIsReadAsAmountsNotMisprints():
    result = parseSummary(summaryAt('2014/Solana/Solana-Executive-Summary-2014.pdf'), 2014, LINES, ALIASES)
    assert values(result)[('performance.revenue', 2014)] == Decimal('141203192.33')
    assert 'malformed_amount' not in kinds(result) and 'engine_disagreement' not in kinds(result)


def testChangeColumnFindingSaysWhatIsPrintedInPesos():
    issue = next(item for item in documentChecks(
        [Figure("position.assets", 2016, Decimal("100.00"), None, "Assets", 1), Figure("position.assets", 2015, Decimal("40.00"), None, "Assets", 1)],
        {"position.assets": Decimal("50.00")}, 2016) if item.kind == "change_mismatch")
    assert issue.message == "Assets: the Increase (Decrease) column shows ₱50.00, but the 2016 amount less the 2015 amount is ₱60.00."


def testOneSplitRowDoesNotSwapAFullerReadingForAShorterOne():
    result = parseSummary(summaryAt('2022/Alcala/Alcala-Executive-Summary-2022.pdf'), 2022, LINES, ALIASES)
    assert values(result)[('budget.obligations_current', 2022)] == Decimal('236652998.88')
    assert 'read_with_fallback_engine' not in kinds(result)


def testStaleBudgetHeaderIsRestoredOnlyWhenThePreviousReportProvesIt():
    from coa.checks import staleHeaderFigures
    earlier = parseSummary(summaryAt('2020/Alcala/Alcala-Executive-Summary-2020.pdf'), 2020, LINES, ALIASES).figures
    result = parseSummary(summaryAt('2021/Alcala/Alcala-Executive-Summary-2021.pdf'), 2021, LINES, ALIASES)
    assert 'header_year_context_changed' in kinds(result)
    assert ('budget.appropriations_current', 2021) not in values(result)

    restored = {(figure.code, figure.fiscalYear): figure.value for figure in staleHeaderFigures(result.evidence, earlier, 2021, LINES, ALIASES)}
    # The 2022 report prints 211,666,466.12 for 2021; the 2020 report's own 2020 figure is 197,549,721.31.
    assert restored[('budget.appropriations_current', 2021)] == Decimal('211666466.12')
    assert restored[('budget.appropriations_current', 2020)] == Decimal('197549721.31')

    contradicted = [Figure(figure.code, figure.fiscalYear, figure.value + 1, figure.variant, figure.sourceLabel, figure.page) for figure in earlier]
    assert staleHeaderFigures(result.evidence, contradicted, 2021, LINES, ALIASES) == []
    assert staleHeaderFigures(result.evidence, [], 2021, LINES, ALIASES) == []


def testSplitRowsFromTheSecondReaderStillConfirmButNeverContradict():
    # pdfium prints Iguig 2017's amounts on the lines below each label; its own row parser pairs them wrongly.
    result = parseSummary(summaryAt('2017/Iguig/Iguig-Executive-Summary-2017.pdf'), 2017, LINES, ALIASES)
    assert values(result)[('performance.expenses', 2017)] == Decimal('77553492.79')
    assert 'engine_disagreement' not in kinds(result)


def testSplitRowConfirmationStillNeedsTheSameLabel():
    candidate = Figure("performance.revenue", 2025, Decimal("100.00"), None, "Income", 1)
    pages = [(1, "Financial Highlights\n2025 2024\nExpenses\n100.00\n90.00 10.00")]
    result = confirmDigits(Result([candidate], [], {}), Path("unused.pdf"), "pdfium", lambda engine: pages, 2025, LINES, ALIASES)
    assert any(issue.kind == "engine_disagreement" for issue in result.issues)
