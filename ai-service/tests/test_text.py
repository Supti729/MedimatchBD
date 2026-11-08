from app.text import normalise, strengths


def test_units_digits_and_abbreviations_are_normalised():
    assert normalise("Napa 500 MG Tab") == "napa 500mg tablet"
    assert normalise("PCM ৫০০ মি.গ্রা. ট্যাবলেট") == "paracetamol 500mg ট্যাবলেট"
    assert normalise("Ace Syp 120 mg/5 ml") == "ace syrup 120mg/5ml"


def test_strengths_are_extracted():
    assert strengths("Napa 500 mg tablet") == {"500mg"}
    assert strengths("Ace syrup 120 mg/5 ml") == {"120mg/5ml"}
    assert strengths("নাপা ৫০০ মি.গ্রা.") == {"500mg"}
    assert strengths("paracetamol") == set()
