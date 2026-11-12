"""A transparent automated-check score, not a probability of financial accuracy."""

from typing import Any


def confidenceScore(figures: list[Any], issues: list[dict[str, Any]]) -> int:
    if not figures:
        return 0
    # Count a finding once even if both readers report it. Keep the strongest severity.
    deductions: dict[tuple[Any, ...], int] = {}
    for issue in issues:
        key = tuple(issue.get(field) for field in (
            'document_kind', 'kind', 'code', 'fiscalYear', 'page',
        ))
        deduction = {'error': 25, 'warning': 10}.get(issue.get('severity'), 0)
        deductions[key] = max(deductions.get(key, 0), deduction)
    return max(0, 100 - sum(deductions.values()))
