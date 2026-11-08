import pytest
from fastapi.testclient import TestClient

from app.config import Settings, get_settings
from app.embedder import FakeEmbedder
from app.main import app


@pytest.fixture
def client():
    """App with the fast fake embedder and an API key configured."""
    app.state.embedder = FakeEmbedder()
    app.dependency_overrides[get_settings] = lambda: Settings(api_key="test-key", score_floor=0.0, max_candidates=5)
    with TestClient(app) as c:
        yield c
    app.dependency_overrides.clear()
    app.state.embedder = None
