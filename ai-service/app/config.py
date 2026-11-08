"""Settings, read from environment variables prefixed with MEDIMATCH_AI_ (or a .env file)."""

from functools import lru_cache

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_prefix="MEDIMATCH_AI_", env_file=".env", extra="ignore")

    model_name: str = "intfloat/multilingual-e5-small"
    api_key: str = ""            # shared secret sent by Laravel as X-API-Key; empty = no auth (local only)
    device: str = "cpu"
    max_candidates: int = 200    # upper bound per request, protects the CPU
    max_text_length: int = 500
    cache_size: int = 5000       # cached embeddings (catalogue/listing texts repeat a lot)
    # Similarity values below this map to a score of 0. e5 models give compressed cosine values
    # (unrelated medicine texts still score ~0.80), so scores are rescaled for readability.
    # Chosen from the evaluation (see evaluation/results/report.md).
    score_floor: float = 0.80


@lru_cache
def get_settings() -> Settings:
    return Settings()
