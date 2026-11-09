import re
from decimal import Decimal

# A peso amount as COA prints it: optional peso sign, thousands commas, two decimals,
# negatives in parentheses. A few reports drop the second decimal. A lone dash means nothing was reported.
AMOUNT = r"\(?\s?[₱P]?\s?\(?\s?-?(?:\d{1,3}(?:,\d{3})+|\d{1,3})\.\d{1,2}(?!\d*\s?%)\s?\)?"
DASH = r"(?<![\w.])-(?![\w.])"
TOKEN = re.compile(rf"{AMOUNT}|{DASH}")
PERCENT = re.compile(r"\(?-?\d+(?:\.\d+)?\)?\s?%\)?")


def parseAmount(token: str) -> Decimal | None:
    token = token.strip()
    if token == "-":
        return None
    isNegative = "(" in token or "-" in token
    digits = re.sub(r"[^\d.]", "", token)
    value = Decimal(digits)
    return -value if isNegative else value
