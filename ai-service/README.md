# Medimatch BD — AI matching service

A small Python **FastAPI** service that ranks medicine descriptions by meaning, using the
multilingual Sentence Transformers model **`intfloat/multilingual-e5-small`**. Laravel calls it to:

- rank listings that already passed the hard rules for a request (wording relevance);
- suggest the catalogue medicine a member meant when they typed free text (`paracitamol`, `pcm 500 tab`, Bangla…);
- suggest medicines when a keyword search finds nothing.

## What it does NOT do

It never diagnoses, prescribes, recommends treatment, judges medical suitability, decides that two
medicines are equivalent or approves documents. It only receives medicine descriptions — never names,
contact details, locations, notes or documents. Identity, strength, dosage form, eligibility and
availability are enforced in Laravel **before** anything is sent here, and again on the way back.

## Run it

Python 3.11+ (tested on 3.13).

```bash
cd ai-service
python3 -m venv .venv
.venv/bin/pip install torch --index-url https://download.pytorch.org/whl/cpu   # small CPU-only PyTorch
.venv/bin/pip install -r requirements-dev.txt
cp .env.example .env            # set MEDIMATCH_AI_API_KEY to the same value as AI_SERVICE_KEY in Laravel's .env
.venv/bin/uvicorn app.main:app --port 8001
```

The first start downloads the model (~470 MB) from Hugging Face into `~/.cache/huggingface`
(set `HF_HOME` to change it). Later starts load it in a few seconds.

Then in Laravel: set `AI_SERVICE_URL=http://127.0.0.1:8001` and `AI_SERVICE_KEY`, and switch on
**Admin → Settings → Use AI-assisted matching**. The settings page shows whether the service is online.
If it is stopped or slow (timeout 3 s), Laravel automatically uses keyword matching and retries after a minute.

## API

| Method | Path | Description |
|---|---|---|
| `GET` | `/health` | `{"status": "ok", "model": "...", "score_floor": 0.8}` |
| `POST` | `/v1/rank` | Rank candidates against a query. Requires header `X-API-Key` when an API key is configured. |

```json
POST /v1/rank
{"query": "paracitamol 500 tab",
 "candidates": [{"id": 1, "text": "Napa 500 — Paracetamol 500 mg Tablet"},
                {"id": 2, "text": "Seclo 20 — Omeprazole 20 mg Capsule"}],
 "top_k": 10}

200 OK
{"model": "intfloat/multilingual-e5-small",
 "results": [{"id": "1", "similarity": 0.93, "score": 65}, {"id": "2", "similarity": 0.8355, "score": 18}],
 "disclaimer": "Text similarity only. Not medical advice and not a statement that medicines are equivalent."}
```

`similarity` is cosine similarity; `score` rescales it to 0–100 (values ≤ `score_floor` become 0).
Limits: 200 candidates, 500 characters per text, unique ids. Errors: `401` bad key, `422` invalid input, `503` model not loaded.

## Text processing

`app/text.py` normalises both sides before embedding: lower-case, Bangla digits → ASCII, `500 mg` → `500mg`,
`মি.গ্রা.` → `mg`, and pharmacy shorthand (`tab`, `cap`, `syp`, `pcm`…). E5 requires the `query:` / `passage:` prefixes,
which `app/embedder.py` adds. Embeddings are cached in memory (catalogue texts repeat a lot).

## Tests and evaluation

```bash
.venv/bin/python -m pytest -q                         # fast: uses a fake embedder, no model needed
HF_HOME=.hf-cache .venv/bin/python -m evaluation.evaluate   # keyword vs semantic on the labelled dataset
```

See [`evaluation/README.md`](evaluation/README.md) for the dataset, results, model choice and thresholds.
