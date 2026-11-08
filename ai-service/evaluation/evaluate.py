"""
Compare keyword matching with semantic matching on the labelled dataset.

    HF_HOME=.hf-cache .venv/bin/python -m evaluation.evaluate

Systems
  keyword_all_terms  every query word must appear in the listing text (what Part 2's search does)
  bm25               classic BM25 keyword ranking (a stronger keyword baseline)
  <model>            semantic similarity with a Sentence Transformers model
  <model> + rules    the same, after the hard rules the app applies (strength and dosage form must match
                     when the query states them) — this is how the model is used inside Medimatch BD

Metrics (queries that have at least one relevant listing)
  P@5   precision in the top 5      R@5  recall in the top 5      MRR  mean reciprocal rank
  Hit@1 share of queries whose first result is relevant
Queries with no relevant listing ("not_in_corpus") are used to study the acceptance threshold.

Results are written to evaluation/results/. Numbers are whatever the run produces — nothing is edited by hand.
"""

from __future__ import annotations

import json
import math
import platform
import sys
import time
from collections import Counter, defaultdict
from datetime import datetime, timezone
from pathlib import Path

import numpy as np

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from app.embedder import SentenceTransformerEmbedder  # noqa: E402
from app.text import normalise, strengths  # noqa: E402

HERE = Path(__file__).parent
MODELS = ["intfloat/multilingual-e5-small", "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2"]
K = 5
FORM_WORDS = {
    "tablet": "tablet", "tablets": "tablet", "ট্যাবলেট": "tablet",
    "capsule": "capsule", "capsules": "capsule", "ক্যাপসুল": "capsule",
    "syrup": "syrup", "সিরাপ": "syrup",
    "inhaler": "inhaler", "powder": "powder", "sachet": "powder", "sachets": "powder",
}


def corpus_text(item: dict) -> str:
    """Same shape Laravel sends for a listing: brand — generic strength form."""
    return f"{item['brand']} — {item['generic']} {item['strength']} {item['form']}"


# ------------------------------------------------------------------ hard rules
def passes_rules(query: str, item: dict) -> bool:
    """Strength and form must match when the query states them (mirrors the structured fields in the app)."""
    q_strengths = strengths(query)
    if q_strengths and not q_strengths <= strengths(item["strength"]):
        return False
    q_forms = {FORM_WORDS[w] for w in normalise(query).split() if w in FORM_WORDS}
    return not q_forms or item["form"].lower() in q_forms


# ------------------------------------------------------------------ keyword baselines
def keyword_all_terms(query: str, corpus: list[dict]) -> list[tuple[int, float]]:
    terms = normalise(query).split()
    texts = {c["id"]: normalise(corpus_text(c)) for c in corpus}
    hits = [(cid, float(sum(t in text.split() for t in terms))) for cid, text in texts.items() if all(t in text for t in terms)]
    return sorted(hits, key=lambda x: (-x[1], x[0]))


class BM25:
    def __init__(self, corpus: list[dict], k1: float = 1.5, b: float = 0.75):
        self.docs = {c["id"]: normalise(corpus_text(c)).split() for c in corpus}
        self.avgdl = sum(map(len, self.docs.values())) / len(self.docs)
        df = Counter(t for words in self.docs.values() for t in set(words))
        n = len(self.docs)
        self.idf = {t: math.log(1 + (n - f + 0.5) / (f + 0.5)) for t, f in df.items()}
        self.k1, self.b = k1, b

    def rank(self, query: str) -> list[tuple[int, float]]:
        terms = normalise(query).split()
        scores = []
        for cid, words in self.docs.items():
            tf = Counter(words)
            s = sum(self.idf.get(t, 0) * tf[t] * (self.k1 + 1) / (tf[t] + self.k1 * (1 - self.b + self.b * len(words) / self.avgdl)) for t in terms)
            if s > 0:
                scores.append((cid, s))
        return sorted(scores, key=lambda x: (-x[1], x[0]))


# ------------------------------------------------------------------ metrics
def metrics(ranked_ids: list[int], relevant: set[int]) -> dict:
    top = ranked_ids[:K]
    hits = [i for i in top if i in relevant]
    rr = next((1 / (pos + 1) for pos, i in enumerate(ranked_ids) if i in relevant), 0.0)
    return {"p@5": len(hits) / K, "r@5": len(hits) / len(relevant), "mrr": rr, "hit@1": float(bool(top) and top[0] in relevant)}


def mean(values) -> float:
    values = list(values)
    return round(sum(values) / len(values), 3) if values else 0.0


def evaluate_system(name: str, rank_fn, queries: list[dict]) -> dict:
    per_query, by_cat = [], defaultdict(list)
    for q in queries:
        if not q["rel"]:
            continue
        ranked = rank_fn(q["q"])
        m = metrics([i for i, _ in ranked], set(q["rel"]))
        per_query.append({"q": q["q"], "cat": q["cat"], **m, "top3": [i for i, _ in ranked[:3]]})
        by_cat[q["cat"]].append(m)
    overall = {k: mean(r[k] for r in per_query) for k in ("p@5", "r@5", "mrr", "hit@1")}
    categories = {cat: {k: mean(r[k] for r in rows) for k in ("p@5", "r@5", "mrr", "hit@1")} for cat, rows in by_cat.items()}
    return {"system": name, "overall": overall, "by_category": categories, "per_query": per_query}


def threshold_study(embedder, corpus, queries) -> list[dict]:
    """For each similarity threshold: how many positive queries still get a correct top result, and how many
    not-in-corpus queries would wrongly get any result (top similarity >= threshold, after hard rules)."""
    texts = [corpus_text(c) for c in corpus]
    P = embedder.embed_passages(texts)
    rows = []
    tops = []
    for q in queries:
        sims = P @ embedder.embed_queries([q["q"]])[0]
        allowed = [i for i, c in enumerate(corpus) if passes_rules(q["q"], c)]
        if not allowed:
            tops.append((q, None, -1.0))
            continue
        best = max(allowed, key=lambda i: sims[i])
        tops.append((q, corpus[best]["id"], float(sims[best])))
    positives = [(q, cid, s) for q, cid, s in tops if q["rel"]]
    negatives = [(q, cid, s) for q, cid, s in tops if not q["rel"]]
    for t in np.round(np.arange(0.80, 0.951, 0.01), 2):
        correct = sum(1 for q, cid, s in positives if s >= t and cid in q["rel"])
        false_accept = sum(1 for _, _, s in negatives if s >= t)
        rows.append({"threshold": float(t), "correct_top1_accepted": correct, "positives": len(positives), "false_accepts": false_accept, "negatives": len(negatives)})
    return rows, {q["q"]: round(s, 3) for q, _, s in negatives}


def main() -> None:
    data = json.loads((HERE / "dataset.json").read_text())
    corpus, queries = data["corpus"], data["queries"]
    by_id = {c["id"]: c for c in corpus}
    results, studies, timings = [], {}, {}

    results.append(evaluate_system("keyword_all_terms", lambda q: keyword_all_terms(q, corpus), queries))
    bm25 = BM25(corpus)
    results.append(evaluate_system("bm25", bm25.rank, queries))
    results.append(evaluate_system("bm25 + rules", lambda q: [(i, s) for i, s in bm25.rank(q) if passes_rules(q, by_id[i])], queries))

    texts = [corpus_text(c) for c in corpus]
    ids = [c["id"] for c in corpus]
    for model in MODELS:
        started = time.perf_counter()
        embedder = SentenceTransformerEmbedder(model)
        load_s = time.perf_counter() - started
        P = embedder.embed_passages(texts)

        def semantic(q, rules=False, P=P, embedder=embedder):
            sims = P @ embedder.embed_queries([q])[0]
            ranked = sorted(zip(ids, sims.tolist()), key=lambda x: -x[1])
            return [(i, s) for i, s in ranked if not rules or passes_rules(q, by_id[i])]

        short = model.split("/")[-1]
        results.append(evaluate_system(short, semantic, queries))
        results.append(evaluate_system(f"{short} + rules", lambda q, f=semantic: f(q, rules=True), queries))

        started = time.perf_counter()
        for q in queries:
            embedder.embed_queries([q["q"] + " timing"])  # bypass the cache for a fair per-query timing
        timings[short] = {"load_seconds": round(load_s, 1), "ms_per_query": round((time.perf_counter() - started) * 1000 / len(queries), 1)}
        studies[short] = threshold_study(embedder, corpus, queries)

    out = HERE / "results"
    out.mkdir(exist_ok=True)
    meta = {"run_at": datetime.now(timezone.utc).isoformat(timespec="seconds"), "python": platform.python_version(), "machine": platform.machine(),
            "corpus_size": len(corpus), "queries": len(queries), "positive_queries": sum(1 for q in queries if q["rel"])}
    (out / "results.json").write_text(json.dumps({"meta": meta, "timings": timings, "systems": results,
                                                   "threshold_study": {k: v[0] for k, v in studies.items()},
                                                   "negative_top_similarity": {k: v[1] for k, v in studies.items()}}, indent=2, ensure_ascii=False))
    write_report(out / "report.md", meta, results, timings, studies, queries)
    print((out / "report.md").read_text())


def write_report(path: Path, meta, results, timings, studies, queries) -> None:
    ceiling = mean(min(len(q["rel"]), K) / K for q in queries if q["rel"])
    lines = [
        "# Matching evaluation — keyword vs semantic",
        "",
        f"Generated by `evaluation/evaluate.py` on {meta['run_at']} (Python {meta['python']}, {meta['machine']}, CPU). "
        f"Corpus: {meta['corpus_size']} listings. Queries: {meta['queries']} ({meta['positive_queries']} with relevant listings, "
        f"{meta['queries'] - meta['positive_queries']} with none).",
        "",
        f"A listing is relevant when it has the same ingredient, strength and form as the query asks for. Most queries have only 1–3 relevant listings, "
        f"so the best possible P@5 on this set is **{ceiling}**.",
        "",
        "## Overall",
        "",
        "| System | P@5 | R@5 | MRR | Hit@1 |",
        "|---|---|---|---|---|",
    ]
    for r in results:
        o = r["overall"]
        lines.append(f"| {r['system']} | {o['p@5']} | {o['r@5']} | {o['mrr']} | {o['hit@1']} |")
    cats = list(dict.fromkeys(q["cat"] for q in queries if q["rel"]))
    lines += ["", "## MRR by query type", "", "| System | " + " | ".join(cats) + " |", "|---|" + "---|" * len(cats)]
    for r in results:
        lines.append(f"| {r['system']} | " + " | ".join(str(r["by_category"].get(c, {}).get("mrr", "–")) for c in cats) + " |")
    lines += ["", "## Speed (CPU)", "", "| Model | Load time | Time per query |", "|---|---|---|"]
    for m, t in timings.items():
        lines.append(f"| {m} | {t['load_seconds']} s | {t['ms_per_query']} ms |")
    for model, (rows, negatives) in studies.items():
        lines += ["", f"## Acceptance threshold — {model} (+ rules)", "",
                  "Top-1 similarity on not-in-corpus queries: " + ", ".join(f"`{q}` {s}" for q, s in negatives.items()), "",
                  "| Threshold | Correct top-1 accepted | False accepts (not in corpus) |", "|---|---|---|"]
        for row in rows:
            lines.append(f"| {row['threshold']:.2f} | {row['correct_top1_accepted']}/{row['positives']} | {row['false_accepts']}/{row['negatives']} |")
    lines += ["", "## Failures of the best system (first result not relevant)", ""]
    best = max(results, key=lambda r: r["overall"]["mrr"])
    fails = [p for p in best["per_query"] if not p["hit@1"]]
    lines += [f"System: **{best['system']}**", ""] + ([f"- `{p['q']}` ({p['cat']}): top-3 ids {p['top3']}" for p in fails] or ["- none"])
    path.write_text("\n".join(lines) + "\n")


if __name__ == "__main__":
    main()
