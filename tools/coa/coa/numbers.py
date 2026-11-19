import re
from decimal import Decimal

_CURRENCY = r"(?:₱|P=?)"
_NUMBER = r"(?:[0-9]{1,3}(?:,[0-9]{3})+|[0-9]+)\.[0-9]{1,2}"
_POSITIVE = rf"(?:{_CURRENCY}\s*)?{_NUMBER}"
_NEGATIVE = rf"(?:{_CURRENCY}\s*[-−]\s*|-(?:{_CURRENCY}\s*)?|−\s*(?:{_CURRENCY}\s*)?){_NUMBER}"
_ACCOUNTING = rf"(?:\(\s*(?:{_CURRENCY}\s*)?{_NUMBER}\s*\)|{_CURRENCY}\s*\(\s*{_NUMBER}\s*\))"
_BODY = rf"(?:{_ACCOUNTING}|{_NEGATIVE}|{_POSITIVE})"
_ADJACENT = r"\w.,()₱$€£¥₹₽₩=+%−–—\-"
# Do not salvage a suffix from malformed grouping, signs, currencies or percentages.
# "Allotments – 58,465,219.29" (Peñablanca 2022): a spaced dash after a word is label punctuation, not a sign.
_LEFT = rf"(?<![{_ADJACENT}])" + "".join(
    rf"(?:(?<=[A-Za-z] [–—]{' ' * count})|(?<![₱P(=+−–—]{' ' * count}))" for count in range(1, 5))
_RIGHT = rf"(?![{_ADJACENT}])(?!\s*%)"
AMOUNT = rf"{_LEFT}{_BODY}{_RIGHT}"
DASH = rf"{_LEFT}-{_RIGHT}"
TOKEN = re.compile(rf"{AMOUNT}|{DASH}")
PERCENT = re.compile(r"\(?[-−]?\d+(?:\.\d+)?\)?\s?%\)?")
_VALID_AMOUNT = re.compile(_BODY)
_SPLIT_DIGITS = re.compile(r"(?<![\d,.])(\d{1,2}) (\d{1,2}(?:,\d{3})+\.\d{2})(?![\d,])")


def joinSplitDigits(text: str, otherReading: str) -> str:
    """Solana 2014's text layer spaces the first digit off ("1 41,203,192.33" for 141,203,192.33). A line's pairs are
    joined only when each joined amount is correctly grouped, the other reader prints it on the same page, and the
    row's own change column proves the result (current minus prior). Aparri 2015 drops a cell, so it stays as printed."""
    printed = set(re.findall(r"\d{1,3}(?:,\d{3})+\.\d{2}", otherReading))

    def join(found: re.Match) -> str:
        joined = found[1] + found[2]
        return joined if len(joined.split(",")[0]) <= 3 and joined in printed else found[0]

    def proven(line: str) -> str:
        joined = _SPLIT_DIGITS.sub(join, line)
        if joined == line:
            return line
        amounts = []
        for token in TOKEN.findall(joined):
            try:
                amounts.append(parseAmount(token))
            except ValueError:
                return line
        if len(amounts) < 3 or None in amounts[:3]:
            return line
        current, prior, change = amounts[:3]
        return joined if min(abs(current - prior - change), abs(prior - current - change)) <= Decimal("1.00") else line

    return "\n".join(proven(line) for line in text.split("\n"))


def peso(value) -> str:
    """₱1,234.56, a negative in parentheses, for reviewer messages."""
    return f"(₱{-value:,.2f})" if value < 0 else f"₱{value:,.2f}"


_NUMBERING = re.compile(r"^\s*(?:[a-z]|[0-9]+|[ivx]+)[.)]\s+", re.I)
_TRAILING_PERCENTS = re.compile(rf"(?:\s*{PERCENT.pattern})+\s*$")
# Digits, a stray % or $, or a parenthesis or peso mark with no amount beside it.
_LEFTOVER_MARK = re.compile(r"\d|%|\$|\(\s*(?:P=?|₱)?\s*(?:\s|$)|(?:^|\s)\)|(?:^|\s)(?:P=?|₱)(?:\s|$)")


def hasMisprintedAmount(line: str) -> bool:
    """Marks left after removing every readable amount, the closing percentages and list numbering were printed
    malformed ("48,014,545,75", "347,374.045.73"). Nothing left means the row's cells wrapped or split instead."""
    leftover = TOKEN.sub(" ", _TRAILING_PERCENTS.sub(" ", _NUMBERING.sub("", line)))
    return _LEFTOVER_MARK.search(leftover) is not None


def parseAmount(token: str) -> Decimal | None:
    token = token.strip()
    if token == "-":
        return None
    if _VALID_AMOUNT.fullmatch(token) is None:
        raise ValueError(f"Malformed monetary token: {token!r}")
    isNegative = "(" in token or "-" in token or "−" in token
    digits = re.sub(r"[₱P=,\s()−\-]", "", token)
    value = Decimal(digits)
    return value.copy_negate() if isNegative else value
