"""Ranking candidates by semantic similarity to a query."""

from dataclasses import dataclass

import numpy as np

from .embedder import Embedder


@dataclass(frozen=True)
class Ranked:
    id: str
    similarity: float  # cosine similarity in [-1, 1]
    score: int         # similarity rescaled to 0–100 for display/combination in Laravel


def to_score(similarity: float, floor: float) -> int:
    """Map cosine similarity to 0–100. Values at or below `floor` become 0."""
    return int(round(max(0.0, min(1.0, (similarity - floor) / (1.0 - floor))) * 100))


def rank(embedder: Embedder, query: str, candidates: list[tuple[str, str]], floor: float, top_k: int | None = None) -> list[Ranked]:
    """Return candidates sorted by similarity (highest first). Candidates are (id, text) pairs."""
    if not candidates:
        return []
    q = embedder.embed_queries([query])[0]
    p = embedder.embed_passages([text for _, text in candidates])
    sims = p @ q
    order = np.argsort(-sims, kind="stable")
    results = [Ranked(candidates[i][0], round(float(sims[i]), 4), to_score(float(sims[i]), floor)) for i in order]
    return results[:top_k] if top_k else results
