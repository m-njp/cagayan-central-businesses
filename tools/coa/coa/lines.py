import csv
import re
from dataclasses import dataclass
from functools import cache
from pathlib import Path

CATALOG = Path(__file__).resolve().parents[3] / "data/towns/finances/lines.csv"

# Group headings that wrap onto the first row of a highlights table.
SECTION_PREFIX = re.compile(
    r"^(?:accounts|particulars|financial (?:position|condition|performance)|results of operations?"
    r"|appropriations?,? allotments?,? and obligations?)\s*:?\s*"
)
QUALIFIER = re.compile(r"\s*\(([^()]*)\)?\s*$")


@dataclass(frozen=True)
class Alias:
    code: str
    pattern: re.Pattern
    variant: str | None


@dataclass(frozen=True)
class Line:
    code: str
    label: str
    group: str
    source: str
    displayed: bool = True


def load(path: Path) -> tuple[dict[str, Line], list[Alias]]:
    lines: dict[str, Line] = {}
    aliases: list[Alias] = []
    with path.open(encoding="utf-8", newline="") as handle:
        for row in csv.DictReader(handle):
            lines[row["code"]] = Line(row["code"], row["label"], row["group"], row["source"], row["displayed"] == "1")
            for entry in row["aliases"].split(";"):
                pattern, _, variant = entry.partition("@")
                aliases.append(Alias(row["code"], re.compile(pattern), variant or None))
    return lines, aliases


@cache
def catalogLabels() -> dict[str, str]:
    return {code: line.label for code, line in load(CATALOG)[0].items()}


def lineLabel(code: str) -> str:
    """The catalog name of a line, such as "Income", for reviewer messages."""
    return catalogLabels().get(code, code)


def normalize(label: str) -> str:
    label = label.lower().replace("’", "'").replace("–", "-").replace("—", "-").replace("₱", "")
    label = re.sub(r"\s*-\s*", " - ", label)
    label = re.sub(r"\s+", " ", label).strip(" :.")
    return re.sub(r"^(?:[a-z]|\d+|[ivx]+)[.)]\s+", "", label)


def match(label: str, aliases: list[Alias], source: str, lines: dict[str, Line]) -> tuple[str, str | None, str] | None:
    """Return (code, variant, qualifier) when the whole label names one catalog line."""
    text = normalize(label)
    while (stripped := SECTION_PREFIX.sub("", text)) != text:
        text = stripped
    qualifier = ""
    if found := QUALIFIER.search(text):
        qualifier = found.group(1).strip()
        text = text[: found.start()].strip()
    for alias in aliases:
        if lines[alias.code].source == source and alias.pattern.fullmatch(text):
            return alias.code, alias.variant or variantFor(alias.code, qualifier), qualifier
    return None


def variantFor(code: str, qualifier: str) -> str | None:
    if code == "performance.revenue" and "subsidy from other funds" in qualifier:
        return "includes_subsidy_from_other_funds"
    if code == "performance.expenses" and "subsidy to other funds" in qualifier:
        return "includes_subsidy_to_other_funds"
    return None
