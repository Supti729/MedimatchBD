"""
FastAPI app for Medimatch BD semantic matching.

The service ONLY ranks candidate texts that Laravel sends. Laravel has already
applied the hard rules (catalogue identity, strength, dosage form, eligibility,
availability, ownership). This service never diagnoses, prescribes, recommends
treatment, judges medical suitability or approves documents, and it never
receives personal data — only medicine descriptions.
"""

from __future__ import annotations

import hmac
import logging
import time
from contextlib import asynccontextmanager
from typing import Annotated

from fastapi import Depends, FastAPI, Header, HTTPException, Request
from pydantic import BaseModel, Field, field_validator

from . import ranking
from .config import Settings, get_settings
from .embedder import Embedder, SentenceTransformerEmbedder

log = logging.getLogger("medimatch.ai")

DISCLAIMER = "Text similarity only. Not medical advice and not a statement that medicines are equivalent."


@asynccontextmanager
async def lifespan(app: FastAPI):
    # Tests set app.state.embedder themselves; otherwise load the real model once.
    if getattr(app.state, "embedder", None) is None:
        settings = get_settings()
        started = time.perf_counter()
        app.state.embedder = SentenceTransformerEmbedder(settings.model_name, settings.device, settings.cache_size)
        log.info("Loaded %s in %.1fs", settings.model_name, time.perf_counter() - started)
    yield


app = FastAPI(title="Medimatch BD AI service", version="1.0.0", lifespan=lifespan)
app.state.embedder = None


def get_embedder(request: Request) -> Embedder:
    embedder = request.app.state.embedder
    if embedder is None:
        raise HTTPException(status_code=503, detail="Model is not loaded yet.")
    return embedder


def require_api_key(settings: Annotated[Settings, Depends(get_settings)], x_api_key: Annotated[str | None, Header()] = None) -> None:
    if settings.api_key and not hmac.compare_digest(settings.api_key, x_api_key or ""):
        raise HTTPException(status_code=401, detail="Invalid or missing API key.")


class Candidate(BaseModel):
    id: str = Field(min_length=1, max_length=64)
    text: str = Field(min_length=1)

    @field_validator("id", mode="before")
    @classmethod
    def id_to_string(cls, v):
        return str(v)


class RankRequest(BaseModel):
    query: str = Field(min_length=1)
    candidates: list[Candidate] = Field(min_length=1)
    top_k: int | None = Field(default=None, ge=1, le=200)


class RankResult(BaseModel):
    id: str
    similarity: float
    score: int


class RankResponse(BaseModel):
    model: str
    results: list[RankResult]
    disclaimer: str = DISCLAIMER


@app.get("/health")
def health(request: Request, settings: Annotated[Settings, Depends(get_settings)]) -> dict:
    embedder = request.app.state.embedder
    return {"status": "ok" if embedder else "loading", "model": getattr(embedder, "model_name", settings.model_name), "score_floor": settings.score_floor}


@app.post("/v1/rank", response_model=RankResponse, dependencies=[Depends(require_api_key)])
def rank(body: RankRequest, embedder: Annotated[Embedder, Depends(get_embedder)], settings: Annotated[Settings, Depends(get_settings)]) -> RankResponse:
    if len(body.candidates) > settings.max_candidates:
        raise HTTPException(status_code=422, detail=f"At most {settings.max_candidates} candidates per request.")
    too_long = [c.id for c in body.candidates if len(c.text) > settings.max_text_length]
    if len(body.query) > settings.max_text_length or too_long:
        raise HTTPException(status_code=422, detail=f"Texts must be at most {settings.max_text_length} characters.")
    if len({c.id for c in body.candidates}) != len(body.candidates):
        raise HTTPException(status_code=422, detail="Candidate ids must be unique.")

    results = ranking.rank(embedder, body.query, [(c.id, c.text) for c in body.candidates], settings.score_floor, body.top_k)
    return RankResponse(model=embedder.model_name, results=[RankResult(id=r.id, similarity=r.similarity, score=r.score) for r in results])
