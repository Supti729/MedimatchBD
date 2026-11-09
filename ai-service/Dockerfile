# Medimatch BD AI service — image for Hugging Face Spaces (Docker SDK) or any container host.
FROM python:3.13-slim

# Hugging Face Spaces run containers as user 1000.
RUN useradd -m -u 1000 user
ENV HOME=/home/user \
    HF_HOME=/home/user/.cache/huggingface \
    PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1 \
    PIP_DISABLE_PIP_VERSION_CHECK=1
WORKDIR /home/user/app

# CPU-only PyTorch (much smaller than the default GPU build), then the service dependencies.
RUN pip install torch==2.15.0 --index-url https://download.pytorch.org/whl/cpu
COPY requirements.txt .
RUN pip install -r requirements.txt

USER user
# Download the model at build time so the service starts quickly and never needs the Hub at runtime.
ARG MODEL_NAME=intfloat/multilingual-e5-small
ENV MEDIMATCH_AI_MODEL_NAME=${MODEL_NAME} HF_HUB_OFFLINE=1
RUN HF_HUB_OFFLINE=0 python -c "from sentence_transformers import SentenceTransformer; SentenceTransformer('${MODEL_NAME}')"

COPY --chown=user app ./app

EXPOSE 7860
# MEDIMATCH_AI_API_KEY must be set as a secret on the host (Space settings → Variables and secrets).
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "7860"]
