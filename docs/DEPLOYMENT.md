# Deploying Medimatch BD (Railway)

```
 Browser ──HTTPS──▶ Railway "web" service (Laravel, Dockerfile in repo root)
                         ├──▶ Railway MySQL (private network)
                         └──HTTPS + X-API-Key──▶ Railway "ai" service (ai-service/Dockerfile)

```

- **web** — the Laravel website. A **volume** keeps uploaded photos and private verification documents.
- **MySQL** — Railway's database.
- **ai** — the AI matching service (FastAPI + `multilingual-e5-small`, ~714 MB RAM). If it is down or
  slow, the website automatically uses keyword matching, so the site never breaks because of it.

> Why not Hugging Face for the AI service? Since late 2026 Hugging Face only hosts **Static** Spaces for free;
> Docker and Gradio Spaces need a PRO subscription. With PRO you can still use the Docker route described
> at the end of this guide.

> **Academic prototype.** This deployment is for demonstrating the capstone. It is not an authorised
> medicine service. Don't upload real prescriptions or ID documents.

**Costs (check railway.com/pricing before you start):** Railway gives new accounts a one-time trial credit;
after that the Hobby plan is US$5/month and includes some usage. The AI service keeps ~0.7 GB of RAM busy,
so expect the total to go **above** the included usage — roughly US$10–15/month for web + MySQL + AI.
To save money between demos you can remove the AI service (or set its replicas to 0); the website keeps
working with keyword matching.

Both images were built and run together locally before this guide was written (MySQL 8 + web with a volume
+ AI container): migrations on first start, waiting for the database, HTTPS links behind a proxy, photo
uploads surviving a redeploy, a custom `PORT` for the AI service and the AI connection all worked.

---

## Part A — create the project, the database and the AI service

### A1. Project and database
1. Sign up at **railway.com** with GitHub and allow access to the `MedimatchBD` repository.
2. **New Project → Deploy from GitHub repo → MedimatchBD.** This first service is the **web** service
   (Railway finds `railway.json` + `Dockerfile` in the repository root). Its first deploy fails until
   Part B is done — that's expected.
3. In the same project: **+ New → Database → MySQL**.

### A2. The AI service
1. **+ New → GitHub Repo → MedimatchBD** again (a second service from the same repository).
2. Open it → **Settings**:
   - **Service name:** `ai`
   - **Source → Root Directory:** `/ai-service` (Railway then uses `ai-service/railway.json` and `ai-service/Dockerfile`)
   - **Networking → Generate Domain**, target port **7860**. Note the `https://ai-….up.railway.app` address.
3. **Variables** of the `ai` service:
   ```
   PORT=7860
   MEDIMATCH_AI_API_KEY=a-long-random-secret
   ```
   Generate the secret with `openssl rand -hex 32` (or any password generator) and keep it for Part B.
   Without this variable the service starts but refuses every matching request (by design).
4. Deploy. The first build takes ~5–10 minutes (PyTorch + model download into the image).
   Check `https://ai-….up.railway.app/health` → `{"status":"ok",…,"api_key_configured":true}`.

## Part B — the website (web service)

### B1. Add a volume (keeps uploads)
Open the **web** service → **⋮ / right-click → Attach volume** (or **Settings → Volumes**). Mount path:
```
/app/storage
```

### B2. Set the variables
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

AI_SERVICE_URL=https://ai-REPLACE-ME.up.railway.app
AI_SERVICE_KEY=THE-SAME-SECRET-AS-MEDIMATCH_AI_API_KEY
AI_SERVICE_TIMEOUT=5
```
- **APP_KEY** — generate it on your computer inside the project: `php artisan key:generate --show`
  and paste the whole output (starts with `base64:`). Never change it after people have accounts.
- `${{MySQL.…}}` are Railway *reference variables*; if your database service has a different name than
  `MySQL`, use that name.
- **APP_URL** — fill in after the next step, then redeploy.

### B3. Get a public address
Web service → **Settings → Networking → Generate Domain**, target port **8080**. Copy the
`https://….up.railway.app` address into `APP_URL`. Railway redeploys automatically after variable changes.

On every start the container: prepares the storage folders on the volume, caches the configuration,
waits for MySQL, runs migrations, loads the reference data (locations + medicine catalogue) and starts
the web server. Railway's health check calls `/up`.

### B4. Create your administrator
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

### B5. (Optional) Demo data for a presentation
Still inside `railway ssh`:
```bash
php artisan db:seed --class=DemoSeeder --force
```
⚠️ This creates the demo accounts with the **public** password `Password123`, including an admin.
Only do this for a short demo; afterwards change those passwords or reset the database.

---

## Updating
- **Website:** push to `main` on GitHub → Railway rebuilds and redeploys automatically. Migrations run on start.
- **AI service:** also redeploys from `main` (Railway rebuilds the `ai` service when files in `ai-service/` change).

## Troubleshooting
| Symptom | Check |
|---|---|
| Deploy fails with "Database not reachable" | MySQL service exists and the `DB_*` reference variables are set on the **web** service |
| 500 error on every page | `APP_KEY` missing or not starting with `base64:` — look at the service logs |
| Links/CSS load over `http://` or not at all | `APP_URL` must be the exact `https://` Railway address |
| Uploaded photos disappear after a deploy | The volume must be mounted at `/app/storage` |
| Admin settings: "Matching service: Not reachable" | The `ai` service is still building/crashed (check its logs), or `AI_SERVICE_URL` is wrong |
| AI calls return 401 | `AI_SERVICE_KEY` (web) ≠ `MEDIMATCH_AI_API_KEY` (ai) |
| AI calls return 503 "not configured" | `MEDIMATCH_AI_API_KEY` is missing on the `ai` service |
| `ai` service runs out of memory | Give it at least 1 GB RAM (it uses ~0.7 GB) |
| New members can't verify | `MEDIMATCH_SHOW_DEV_CODES=true`, or configure a real mail provider (e.g. `MAIL_MAILER=resend` + `RESEND_API_KEY`) |

## Security notes
- `APP_DEBUG=false` in production (never show stack traces).
- Private verification documents live in `storage/app/private` on the volume and are only streamed to
  authorised reviewers; they are never under `/storage` URLs.
- Secrets (`APP_KEY`, database password, AI key) live only in Railway variables — never in git.
- The AI service refuses to rank anything until its API key is set, and only ever receives medicine descriptions.
- Before any real use: configure email, replace development-mode verification, review the medicine
  catalogue with pharmacists and get the legal approvals described in the project brief.

---

## Alternative: AI service on Hugging Face with PRO
With a Hugging Face PRO subscription you can host the AI service as a Docker Space instead of on Railway:
create a Docker Space, add the secret `MEDIMATCH_AI_API_KEY`, and run
`ai-service/deploy/push-to-huggingface.sh YOUR-USERNAME/medimatch-ai`. Then use
`AI_SERVICE_URL=https://YOUR-USERNAME-medimatch-ai.hf.space` on the web service.

