HEADERS = {"X-API-Key": "test-key"}
CANDIDATES = [
    {"id": 1, "text": "Seclo 20, Omeprazole 20 mg Capsule"},
    {"id": 2, "text": "Napa 500, Paracetamol 500 mg Tablet"},
    {"id": 3, "text": "Ace Syrup, Paracetamol 120 mg/5 ml Syrup"},
]


def test_health(client):
    body = client.get("/health").json()
    assert body["status"] == "ok"
    assert body["model"] == "fake-bag-of-words"


def test_rank_orders_by_similarity(client):
    res = client.post("/v1/rank", headers=HEADERS, json={"query": "paracetamol 500 mg tablet", "candidates": CANDIDATES})
    assert res.status_code == 200
    body = res.json()
    assert [r["id"] for r in body["results"]][0] == "2"
    assert body["results"][0]["similarity"] >= body["results"][1]["similarity"] >= body["results"][2]["similarity"]
    assert all(0 <= r["score"] <= 100 for r in body["results"])
    assert "Not medical advice" in body["disclaimer"]


def test_top_k_limits_results(client):
    res = client.post("/v1/rank", headers=HEADERS, json={"query": "paracetamol", "candidates": CANDIDATES, "top_k": 1})
    assert len(res.json()["results"]) == 1


def test_api_key_is_required(client):
    assert client.post("/v1/rank", json={"query": "x", "candidates": CANDIDATES}).status_code == 401
    assert client.post("/v1/rank", headers={"X-API-Key": "wrong"}, json={"query": "x", "candidates": CANDIDATES}).status_code == 401


def test_input_validation(client):
    post = lambda body: client.post("/v1/rank", headers=HEADERS, json=body)
    assert post({"query": "", "candidates": CANDIDATES}).status_code == 422
    assert post({"query": "x", "candidates": []}).status_code == 422
    assert post({"query": "x" * 501, "candidates": CANDIDATES}).status_code == 422
    assert post({"query": "x", "candidates": [{"id": 1, "text": "a"}] * 2}).status_code == 422          # duplicate ids
    assert post({"query": "x", "candidates": [{"id": i, "text": "a"} for i in range(6)]}).status_code == 422  # > max_candidates
    assert post({"query": "x"}).status_code == 422


def test_503_when_model_not_loaded(client):
    from app.main import app
    app.state.embedder = None
    assert client.post("/v1/rank", headers=HEADERS, json={"query": "x", "candidates": CANDIDATES}).status_code == 503


def test_deployed_service_refuses_to_run_without_a_key(client):
    from app.config import Settings, get_settings
    from app.main import app
    app.dependency_overrides[get_settings] = lambda: Settings(api_key="", require_api_key=True)
    res = client.post("/v1/rank", json={"query": "x", "candidates": CANDIDATES})
    assert res.status_code == 503
    assert "MEDIMATCH_AI_API_KEY" in res.json()["detail"]
    assert client.get("/health").json()["api_key_configured"] is False
