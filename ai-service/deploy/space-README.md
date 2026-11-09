---
title: Medimatch BD AI
emoji: 💊
colorFrom: green
colorTo: blue
sdk: docker
app_port: 7860
pinned: false
short_description: Semantic medicine matching API for Medimatch BD
---

# Medimatch BD — AI matching service

FastAPI service that ranks medicine descriptions with `intfloat/multilingual-e5-small`.
Used by the Medimatch BD web application (Software Engineering capstone, Bangladesh).

- `GET /health` — status and model name
- `POST /v1/rank` — rank candidate texts against a query (requires the `X-API-Key` header)

It only ranks medicine descriptions. It never diagnoses, prescribes, decides that medicines are
equivalent or approves documents, and it receives no personal data.

Source code and documentation: the `ai-service/` folder of the Medimatch BD repository.
