"""Optional progress callbacks keep parsing independent of the terminal and web app."""

from collections.abc import Callable
from typing import Any

Progress = Callable[[dict[str, Any]], None] | None


def report(progress: Progress, eventType: str, **details: Any) -> None:
    if progress is not None:
        progress({"type": eventType, **details})


def preview(progress: Progress, figure: Any) -> None:
    report(progress, "figure", figure={
        "line_code": figure.code, "fiscal_year": figure.fiscalYear,
        "basis": getattr(figure, "basis", "actual"), "value": f"{figure.value:.2f}",
        "location": getattr(figure, "location", None) or f"page {figure.page}",
    })
