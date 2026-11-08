"""
Text normalisation applied to BOTH queries and listing texts before embedding.

Only surface forms are normalised (units, digits, common abbreviations). Nothing
here maps symptoms to medicines or one medicine to another: the service never
gives medical advice and never decides medical equivalence.
"""

import re
import unicodedata

BANGLA_DIGITS = str.maketrans("০১২৩৪৫৬৭৮৯", "0123456789")

# Common pharmacy shorthand -> full word (surface form only).
ABBREVIATIONS = {
    "tab": "tablet", "tabs": "tablets", "cap": "capsule", "caps": "capsules",
    "syp": "syrup", "susp": "suspension", "inj": "injection", "oint": "ointment",
    "pcm": "paracetamol", "mgs": "mg",
}

UNITS = r"(?:mg|ml|mcg|iu|g)"


def normalise(text: str) -> str:
    """Lower-case, NFKC, Bangla digits -> ASCII, '500 mg' -> '500mg', expand shorthand, tidy spaces."""
    text = unicodedata.normalize("NFKC", text).translate(BANGLA_DIGITS).lower()
    text = re.sub(r"মি\.?\s?গ্রা\.?", "mg", text)            # Bangla "মি.গ্রা." (milligram)
    text = re.sub(rf"(\d)\s*({UNITS})\b", r"\1\2", text)
    # Keep letters, combining marks (Bangla vowel signs are marks, not \w), digits and / . % -
    text = "".join(c if unicodedata.category(c)[0] in "LMN" or c in "/.%- " else " " for c in text)
    words = [ABBREVIATIONS.get(w, w) for w in text.split()]
    return " ".join(words).strip()


def strengths(text: str) -> set[str]:
    """Strength tokens found in a text, e.g. {'500mg'} or {'120mg/5ml'}."""
    return set(re.findall(rf"\d+(?:\.\d+)?{UNITS}(?:/\d+(?:\.\d+)?{UNITS})?", normalise(text)))
