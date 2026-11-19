import pytest

from coa.confidence import confidenceScore


def finding(severity, kind='digits_mismatch', **context):
    return dict(severity=severity, kind=kind, **context)


def testCleanReportPassesEveryConfiguredCheck():
    assert confidenceScore([object()], []) == 100


def testNoFiguresCannotReceiveFullConfidence():
    assert confidenceScore([], []) == 0


def testWarningsErrorsAndInfoHaveTransparentDeductions():
    assert confidenceScore([object()], [finding('warning'), finding('error', 'malformed_amount'), finding('info', 'note')]) == 65


def testReadersCannotDoubleCountTheSameFinding():
    assert confidenceScore([object()], [finding('warning'), finding('warning'), finding('error')]) == 75


@pytest.mark.parametrize('field', ['code', 'fiscalYear', 'page', 'document_kind'])
def testDistinctSourceFindingsEachReduceConfidence(field):
    assert confidenceScore([object()], [finding('warning', **{field: 1}), finding('warning', **{field: 2})]) == 80


def testScoreCannotBecomeNegative():
    assert confidenceScore([object()], [finding('error', str(index)) for index in range(8)]) == 0
