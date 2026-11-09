# Deploying Medimatch BD (Railway + Hugging Face Spaces)

```
 Browser ──HTTPS──▶ Railway: "web" service (Laravel, Dockerfile in repo root)
                         │  private network
                         ├──▶ Railway: MySQL database
                         └──HTTPS + X-API-Key──▶ Hugging Face Space (ai-service/Dockerfile)
```

- **Railway** runs the Laravel website and a MySQL database. A **volume** keeps uploaded photos and
  private verification documents across redeploys.
- **Hugging Face Spaces** (free CPU) runs the AI matching service. If it is asleep or down, the website
  automatically falls back to keyword matching, so the site never breaks because of it.

> **Academic prototype.** This deployment is for demonstrating the capstone. It is not an authorised
> medicine service. Don't upload real prescriptions or ID documents.

**Costs (check current prices before you start):** Railway gives new accounts a one-time trial credit;
after that the Hobby plan is about US$5/month including usage for a small app like this
(railway.com/pricing). Hugging Face "CPU basic" Spaces are free but sleep after about 48 hours without
visits — open the Space URL a few minutes before a demo to wake it.

Both images were built and run together locally before this guide was written (MySQL 8 container +
web container with a volume + AI container): migrations on first start, waiting for the database,
HTTPS links behind a proxy, photo uploads surviving a redeploy and the AI connection all worked.

---

## Part A — the AI service on Hugging Face (do this first)

### A1. Create the Space
1. Sign up / log in at **huggingface.co**.
2. **New → Space**. Name: e.g. `medimatch-ai`. SDK: **Docker** → template **Blank**.
   Hardware: **CPU basic (free)**. Visibility: **Public** (the API is protected by its own key; a private
   Space would need a Hugging Face token on every request).
3. In the Space: **Settings → Variables and secrets → New secret**
   - Name: `MEDIMATCH_AI_API_KEY`
   - Value: a long random string. Generate one with `openssl rand -hex 32` (or any password generator).
   Keep it — Railway needs the same value.

### A2. Upload the code
You need a Hugging Face **access token** with *write* permission (**Settings → Access Tokens**).

**Option 1 — script (Linux/macOS/Git Bash):** from the repository root:
```bash
ai-service/deploy/push-to-huggingface.sh YOUR-HF-USERNAME/medimatch-ai
```
When git asks for a password, paste the access token.

**Option 2 — web upload:** in the Space, **Files → Add file → Upload files**, and upload
`ai-service/app/` (the whole folder), `ai-service/requirements.txt`, `ai-service/Dockerfile` and
`ai-service/.dockerignore`. Then replace the Space's `README.md` with the contents of
`ai-service/deploy/space-README.md` (it must keep the `---` header block — that tells Hugging Face to use Docker on port 7860).

### A3. Wait for the build and test it
The first build takes about 5–10 minutes (PyTorch + model download). When the Space shows **Running**, open:
```
https://YOUR-HF-USERNAME-medimatch-ai.hf.space/health
```
You should see `{"status":"ok","model":"intfloat/multilingual-e5-small",...}`.
(The exact URL is shown under **⋮ → Embed this Space → Direct URL**.)

---

## Part B — the website on Railway

### B1. Create the project
1. Sign up at **railway.com** with GitHub and allow access to the `MedimatchBD` repository.
2. **New Project → Deploy from GitHub repo → MedimatchBD**. Railway finds `railway.json` and builds the
   `Dockerfile` (≈ 5 minutes). The first deploy will fail until the variables below are set — that's expected.
3. In the same project: **+ New → Database → MySQL**.

### B2. Add a volume (keeps uploads)
Open the web service → **⋮ / right-click → Attach volume** (or **Settings → Volumes**). Mount path:
```
/app/storage
```

### B3. Set the variables
Web service → **Variables → Raw Editor**, paste and adjust:
```
APP_NAME="Medimatch BD"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:REPLACE_ME
APP_URL=https://REPLACE-ME.up.railway.app
PORT=8080

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
MAIL_MAILER=log

# No email/SMS provider is configured, so show verification codes on screen (clearly labelled "Development mode").
MEDIMATCH_SHOW_DEV_CODES=true

AI_SERVICE_URL=https://YOUR-HF-USERNAME-medimatch-ai.hf.space
AI_SERVICE_KEY=THE-SAME-SECRET-AS-ON-HUGGING-FACE
AI_SERVICE_TIMEOUT=5
```
- **APP_KEY** — generate it on your computer inside the project: `php artisan key:generate --show`
  and paste the whole output (starts with `base64:`). Never change it after people have accounts.
- `${{MySQL.…}}` are Railway *reference variables*; if your database service has a different name than
  `MySQL`, use that name.
- **APP_URL** — fill in after the next step, then redeploy.

### B4. Get a public address
Web service → **Settings → Networking → Generate Domain**, target port **8080**. Copy the
`https://….up.railway.app` address into `APP_URL`. Railway redeploys automatically after variable changes.

On every start the container: prepares the storage folders on the volume, caches the configuration,
waits for MySQL, runs migrations, loads the reference data (locations + medicine catalogue) and starts
the web server. Railway's health check calls `/up`.

### B5. Create your administrator
Install the Railway CLI (`npm i -g @railway/cli`), then in the project folder:
```bash
railway login
```
```bash
railway link
```
```bash
railway ssh
```
Inside the container:
```bash
php artisan medimatch:create-admin
```
It asks for name, email and a password (min. 8 characters with letters and numbers).
Log in at `https://YOUR-APP.up.railway.app/admin/login`, open **Admin → Settings**, tick
**Use AI-assisted matching**, save, and check that it says **Matching service: Online**.

### B6. (Optional) Demo data for a presentation
Still inside `railway ssh`:
```bash
php artisan db:seed --class=DemoSeeder --force
```
⚠️ This creates the demo accounts with the **public** password `Password123`, including an admin.
Only do this for a short demo; afterwards change those passwords or reset the database.

---

## Updating
- **Website:** push to `main` on GitHub → Railway rebuilds and redeploys automatically. Migrations run on start.
- **AI service:** run `ai-service/deploy/push-to-huggingface.sh …` again (or re-upload the changed files).

## Troubleshooting
| Symptom | Check |
|---|---|
| Deploy fails with "Database not reachable" | MySQL service exists and the `DB_*` reference variables are set on the **web** service |
| 500 error on every page | `APP_KEY` missing or not starting with `base64:` — look at the service logs |
| Links/CSS load over `http://` or not at all | `APP_URL` must be the exact `https://` Railway address |
| Uploaded photos disappear after a deploy | The volume must be mounted at `/app/storage` |
| Admin settings: "Matching service: Not reachable" | Space is asleep (open its URL), still building, or `AI_SERVICE_URL` is wrong |
| AI calls return 401 | `AI_SERVICE_KEY` (Railway) ≠ `MEDIMATCH_AI_API_KEY` (Space secret) |
| New members can't verify | `MEDIMATCH_SHOW_DEV_CODES=true`, or configure a real mail provider (e.g. `MAIL_MAILER=resend` + `RESEND_API_KEY`) |

## Security notes
- `APP_DEBUG=false` in production (never show stack traces).
- Private verification documents live in `storage/app/private` on the volume and are only streamed to
  authorised reviewers; they are never under `/storage` URLs.
- Secrets (`APP_KEY`, database password, AI key) live only in Railway variables / Space secrets — never in git.
- Before any real use: configure email, replace development-mode verification, review the medicine
  catalogue with pharmacists and get the legal approvals described in the project brief.
