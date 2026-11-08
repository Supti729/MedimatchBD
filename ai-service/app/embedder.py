"""Embedding back-ends. The real one wraps Sentence Transformers; tests use FakeEmbedder."""

from __future__ import annotations

import hashlib
from collections import OrderedDict
from typing import Protocol

import numpy as np

from .text import normalise


class Embedder(Protocol):
    model_name: str

    def embed_queries(self, texts: list[str]) -> np.ndarray: ...

    def embed_passages(self, texts: list[str]) -> np.ndarray: ...


class SentenceTransformerEmbedder:
    """Loads the model once at start-up. Vectors are L2-normalised, so dot product == cosine similarity."""

    def __init__(self, model_name: str, device: str = "cpu", cache_size: int = 5000):
        from sentence_transformers import SentenceTransformer  # heavy import, only when really used

        self.model_name = model_name
        self.model = SentenceTransformer(model_name, device=device)
        # E5 models are trained with "query: " / "passage: " prefixes and need them.
        self.is_e5 = "e5" in model_name.lower()
        self._cache: OrderedDict[str, np.ndarray] = OrderedDict()
        self._cache_size = cache_size

    def embed_queries(self, texts: list[str]) -> np.ndarray:
        return self._embed(texts, "query: " if self.is_e5 else "")

    def embed_passages(self, texts: list[str]) -> np.ndarray:
        return self._embed(texts, "passage: " if self.is_e5 else "")

    def _embed(self, texts: list[str], prefix: str) -> np.ndarray:
        keys = [prefix + normalise(t) for t in texts]
        missing = list(dict.fromkeys(k for k in keys if k not in self._cache))
        if missing:
            vectors = self.model.encode(missing, normalize_embeddings=True, convert_to_numpy=True, batch_size=32)
            for key, vector in zip(missing, vectors):
                self._cache[key] = vector
                if len(self._cache) > self._cache_size:
                    self._cache.popitem(last=False)
        for key in keys:
            self._cache.move_to_end(key)
        return np.stack([self._cache[k] for k in keys])


class FakeEmbedder:
    """Deterministic bag-of-words hashing embedder for fast unit tests (no model download)."""

    model_name = "fake-bag-of-words"

    def __init__(self, dims: int = 256):
        self.dims = dims

    def embed_queries(self, texts: list[str]) -> np.ndarray:
        return np.stack([self._vector(t) for t in texts])

    embed_passages = embed_queries

    def _vector(self, text: str) -> np.ndarray:
        v = np.zeros(self.dims)
        for word in normalise(text).split():
            v[int(hashlib.md5(word.encode()).hexdigest(), 16) % self.dims] += 1.0
        norm = np.linalg.norm(v)
        return v / norm if norm else v
