# Medimatch BD

A privacy-focused, location-based web application that connects people in Bangladesh who need eligible medicines with individuals and authorised organisations who can help through donation or approved affordable-access programmes.

Software Engineering Capstone Project. **This is an academic prototype, not an authorised medicine service.** Real-world medicine redistribution, verification and delivery must follow applicable Bangladesh regulations.

**Team:** Supti Saha (Team Leader, UI/UX, frontend) · Atiar Rahman Rahat (Backend & database) · Emamul Muttakin (AI & supporting modules)

---

## Build plan

| Part | Scope | Status |
|------|-------|--------|
| **1. Frontend** | Laravel + Blade pages, shared design system, responsive layouts | ✅ Done |
| **2. Backend** | MySQL schema, auth, roles/policies, listings, requests, search, matching, messaging, notifications, verification, admin, audit logs | ✅ Done |
| **3. AI integration** | Python FastAPI + Sentence Transformers semantic matching, evaluation, Laravel integration | ✅ Done |

## Tech stack

- **Laravel 13** (PHP 8.3), Blade components, Eloquent, Form Requests, Policies & Gates
- **MySQL 8** (tests also run on in-memory SQLite)
- **Tailwind CSS v4** via **Vite**, fonts self-hosted with `@fontsource`
- Small dependency-free JavaScript (`resources/js/app.js`)
- **Python 3 · FastAPI · Sentence Transformers** (`intfloat/multilingual-e5-small`) for semantic matching — see [`ai-service/`](ai-service/README.md)
- PHPUnit feature tests (115) and pytest (8)

## Running locally

Requirements: PHP 8.3+ (with `pdo_mysql`, `gd`, `fileinfo`), Composer, Node 20+, MySQL 8.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create the database and user, then put the password in `.env` (`DB_PASSWORD`):

```sql
CREATE DATABASE medimatch CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'medimatch'@'localhost' IDENTIFIED BY 'choose-a-password';
GRANT ALL ON medimatch.* TO 'medimatch'@'localhost';
```

```bash
php artisan migrate --seed      # locations, medicine catalogue (+ demo data when APP_ENV=local)
php artisan storage:link        # public listing photos
npm install && npm run build
php artisan serve
```

Open http://localhost:8000.

**AI matching (optional):** start the Python service as described in [`ai-service/README.md`](ai-service/README.md), then
set `AI_SERVICE_URL` and `AI_SERVICE_KEY` in `.env`. The demo data switches AI matching on; without the service running,
the app silently uses keyword matching instead.

### Demo accounts (local only, password `Password123`)

| Role | Login |
|------|-------|
| Administrator | `admin@medimatch.test` (use `/admin/login`) |
| Authorised reviewer | `reviewer@medimatch.test` (use `/admin/login`) |
| Members | `nadia@medimatch.test`, `rahim@medimatch.test`, `farhana@medimatch.test`, `mithila@medimatch.test`, `arif@medimatch.test` |
| Phone-only member | `01811000004` |

The demo data is created by `DemoSeeder` **through the real services**, so it contains genuine matches, notifications, a conversation, a completed hand-over, a pending listing review, a verification waiting for a reviewer and an open report.

### Tests

```bash
php artisan test
```

Runs on in-memory SQLite by default. To run the same suite against MySQL, point `DB_*` at an empty test database, e.g. `DB_CONNECTION=mysql DB_DATABASE=medimatch_test php artisan test`.

## What works (Part 2)

**Accounts**
- Register with email, Bangladeshi phone (any common format, stored as `+8801XXXXXXXXX`) or both; division → district → upazila; optional photo.
- One-time code verification. ⚠️ **Development mode:** no SMS provider is configured and email uses the `log` mailer, so the code is shown on screen (labelled) and written to `storage/logs/laravel.log`. Nothing is delivered.
- Log in with email or phone, remember me, throttling (5 attempts/minute), log out, forgot/reset password (Laravel password broker; email goes to the log in development).
- Suspended accounts are refused at login and signed out on their next request.

**Roles & authorization** (all enforced on the server)
- `user` (one account both gives and receives), `reviewer` (verification documents only), `admin` (everything).
- Gates: `access-admin`, `admin`, `review-verifications`. Policies for listings, requests, conversations, interactions and verification documents.
- Roles/status are never mass-assignable; only an admin can change them (not their own), and every change is audit-logged.

**Listings** — create (catalogue entry + quantity, expiry month, packaging, photos, area, declarations), save as draft, edit, pause, reactivate, cancel. Status transitions are defined once in `ListingStatus::canTransitionTo()` and enforced by `ListingService`. Medicines marked *Not Allowed* can't be listed; *Verification Required* medicines and listings from new, unverified members go to review first. Affordable Access is off unless an admin enables it for an approved programme.

**Requests & matching** — create/edit/cancel; rule-based matching (`MatchingService`): same medicine identity, strength and form, publicly available, not your own, respects donation/affordable preference; nearer listings score higher. New listings are matched against open requests and the requester is notified once per match.

**Search** — database search over brand, generic, strength, form and catalogue aliases (including Bangla aliases), filters (division/district/upazila, type, form, strength, availability), sorting (relevance, newest, nearest, expiry), pagination, grid/list. Blocked, draft, paused, unreviewed, expired listings and listings of suspended members can never appear.

**Hand-overs & messaging** — request a listing (opens a private conversation), donor reserves, either side marks complete or cancels; linked request and listing statuses follow. Medicines that require verification can only be reserved after the requester's prescription has been verified by a reviewer (the donor only sees that it passed). Messages are stored in the database; only the two participants can read them; messages containing phone numbers or email addresses are blocked; read/unread tracking.

**Notifications** — Laravel database notifications for new message, new request, match found, listing status change, verification decision, reservation update, completion, report update and announcements. Header bell with unread count and dropdown, full page with filter, mark one/all read; opening a notification marks it read (and only ever redirects within the site).

**Verification** — upload (JPG/PNG/PDF ≤ 5 MB, consent required) to the private disk; reviewers open it through an access-checked stream (`no-store`), every view is logged; decisions (verified / re-upload / rejected) with a note, status history, notification and audit entry. Reviewers can't review their own submissions. AI is never used for this.

**Admin** — dashboard with live counts and charts (registrations, completions, listings/requests by district, type split) and work queues; users (search, filter, view, suspend/restore/activate, change role); medicine catalogue (add, eligibility, aliases, activate); listing review (approve/reject/block); requests; verification centre; reports (resolve, reject, escalate, suspend member, block listing); interaction monitoring; partners; announcements to all / one division / donors; append-only audit log; platform settings.

**Profile & privacy** — private profile, edit (changing email/phone requires re-verification), public profile with minimum information, privacy settings (photo, contribution count, upazila, who can message), password change (signs out other devices), active sessions list and sign-out, account deletion (closes open listings/requests and removes personal details).

## Known limitations

- **No real email/SMS delivery.** Verification codes, password-reset links and contact messages use the `log` mailer in development. Configure a mail provider (and an SMS provider for phone codes) before any real use.
- **No real-time updates yet.** Messages and notifications appear on the next page load; Laravel Reverb/WebSockets can be added later.
- **Upazila data is partial.** All 8 divisions and 64 districts are seeded; full upazila lists exist only for the main districts (others have "<District> Sadar"). Extend `database/data/locations.php` and re-seed.
- **Catalogue eligibility values are illustrative.** They must be set with qualified pharmacists against current regulations before any real deployment.
- **AI suggestions are conservative.** Easily confused ingredients (e.g. *omeprazole* vs *esomeprazole*) or short Bangla words below the similarity threshold get no suggestion; members can still choose the medicine from the catalogue, and Bangla catalogue aliases make keyword search find them. Thresholds come from a small hand-labelled dataset and need a larger test set before real use.
- **AI service runs on CPU in a single process.** Fine for the prototype (≈11 ms per query); production would need a process manager and monitoring.
- **Photos are not EXIF-stripped yet**; members are told not to include personal information in photos.
- **Partner plans are labels only**; no payments are processed anywhere.

## Project structure

```
app/Enums/                    Status enums with labels, colours, icons and transition rules
app/Models/                   Eloquent models (User, Medicine, MedicineListing, MedicineRequest, Conversation, Interaction, …)
app/Services/                 Business rules: ListingService, MatchingService, RequestService, InteractionService,
                              MessageService, VerificationService, ReportService, UserModerationService, AuditLogger
app/Policies/                 Ownership / participant / reviewer authorization
app/Http/Requests/            Form Request validation
app/Http/Controllers/         Public, Auth/*, Account/*, Admin/*
app/Notifications/            Database notifications
database/migrations/          Schema (29 tables incl. framework tables)
database/seeders/             LocationSeeder, MedicineCatalogueSeeder, DemoSeeder (local only)
resources/views/components/   Design system (layouts, buttons, badges, cards, form fields, dialogs, charts)
docs/prototype/               The original static HTML prototype
tests/Feature/                Public, auth, authorization, listings, requests & matching, hand-over & messaging,
                              notifications, verification & moderation, page smoke tests
```

## AI-assisted matching (Part 3)

| Where | What the AI does | What it never does |
|---|---|---|
| Request matches | Ranks listings that already have the same medicine, strength and form (up to 15 of the 100 relevance points; distance and recency are rules) | Decide identity, strength, form, eligibility or availability |
| Free-text requests | Suggests the catalogue medicine meant (`paracitamol 500 mg` → Napa 500) **only** among entries with the exact same strength and form, similarity ≥ 0.86 and clearly ahead of any other ingredient; shown as "Did you mean…?" | Silently change the request or pick between confusable ingredients |
| Search | When keywords find nothing, suggests the closest single ingredient (labelled) | Show results without saying they are AI suggestions |

**Evaluation** (`ai-service/evaluation/`, 33 listings, 56 hand-labelled queries): mean reciprocal rank **0.99** for
multilingual-e5-small vs **0.56** for the Part 2 keyword search and **0.878** for BM25; misspellings 1.0 vs 0.333;
Bangla 0.938 vs 0.0. The model was chosen over `paraphrase-multilingual-MiniLM-L12-v2` (MRR 0.953 with rules).
Full numbers: [`ai-service/evaluation/results/report.md`](ai-service/evaluation/results/report.md); interpretation and
limitations: [`ai-service/evaluation/README.md`](ai-service/evaluation/README.md).

**Privacy & safety:** only medicine descriptions are sent (`app/Services/Ai/MedicineText.php`); requests carry an API key;
3 s timeout with automatic keyword fallback and a one-minute circuit breaker; AI can be switched off in Admin → Settings;
every match records whether it was `keyword`, `semantic` or `semantic_identity`.
