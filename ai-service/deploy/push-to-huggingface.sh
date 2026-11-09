#!/usr/bin/env bash
# Publish ai-service/ to a Hugging Face Space (Docker SDK).
#
#   ai-service/deploy/push-to-huggingface.sh <hf-username>/<space-name>
#
# Create the Space first on huggingface.co (SDK: Docker, template: Blank, hardware: CPU basic).
# When git asks for a password, paste a Hugging Face access token with "write" permission.
set -euo pipefail

SPACE="${1:?Usage: $0 <hf-username>/<space-name>}"
HERE="$(cd "$(dirname "$0")/.." && pwd)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

git clone "https://huggingface.co/spaces/${SPACE}" "$WORK/space"
cd "$WORK/space"
# Replace the Space contents with the service files.
git rm -rq --ignore-unmatch . >/dev/null 2>&1 || true
cp -r "$HERE/app" "$HERE/requirements.txt" "$HERE/Dockerfile" "$HERE/.dockerignore" .
cp "$HERE/deploy/space-README.md" README.md
find . -name "__pycache__" -type d -prune -exec rm -rf {} +

git add -A
if git diff --cached --quiet; then
  echo "Nothing changed."
else
  git commit -qm "Deploy Medimatch BD AI service"
  git push
  echo "Pushed. Watch the build at https://huggingface.co/spaces/${SPACE}"
fi
