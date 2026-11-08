# Medimatch BD

A privacy-focused, location-based web application that connects people in Bangladesh who need eligible medicines with individuals and authorised organisations who can help through donation or approved affordable-access programmes.

Software Engineering Capstone Project. **This is an academic prototype, not an authorised medicine service.** Real-world medicine redistribution, verification and delivery must follow applicable Bangladesh regulations.

**Team:** Supti Saha (Team Leader, UI/UX, frontend) · Atiar Rahman Rahat (Backend & database) · Emamul Muttakin (AI & supporting modules)

---

## Build plan

| Part | Scope | Status |
|------|-------|--------|
| **1. Frontend** | Laravel + Blade pages, shared design system, responsive layouts, every screen with sample data | ✅ Done (this branch) |
| **2. Backend** | MySQL schema, auth, roles/policies, listings, requests, search, messaging, notifications, verification, admin, audit logs | ⏳ Next |
| **3. AI integration** | Python FastAPI + Sentence Transformers semantic matching, evaluation, Laravel integration | ⏳ Later |

## Tech stack

- **Laravel 13** (PHP 8.3) with Blade components
- **Tailwind CSS v4** built with **Vite**; fonts self-hosted via `@fontsource` (no third-party font requests)
- Small, dependency-free JavaScript (`resources/js/app.js`) for menus, dropdowns, dialogs, filter drawer and the location cascade
- PHPUnit feature tests

## Running locally

Requirements: PHP 8.3+, Composer, Node 20+.

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build        # or `npm run dev` while editing
php artisan serve
```

Open http://localhost:8000. Part 1 needs no database: sessions and cache use files.

Run the tests:

```bash
php artisan test
```

## Part 1: what's included

### Pages (51 routes, all rendering)
- **Public:** Home, Browse, Search results, Medicine details, Public member profile, How It Works, Safety, About, Contact, Privacy Policy, Terms
- **Auth:** Log in, Register, Forgot password, Reset password, Verify account (clearly labelled development mode), Admin log in
- **Member:** Dashboard, Profile, Edit profile, My listings (tabs), Create/Edit listing, My requests, Create/Edit request, Request details with matches, Saved, Messages, Conversation, Notifications, Interaction history, Verification Center, Privacy settings, Security settings
- **Admin:** Dashboard with charts, Users, User details, Medicine catalogue, Listings, Listing review, Requests, Verification Center, Document review, Reports, Report details, Interactions, Partners, Announcements, Audit logs, Settings
- Branded error pages (403, 404, 419, 429, 500, 503)

### What actually works in Part 1
- Browse and search filter, sort and paginate the sample listings by name/generic/alias, division → district → upazila, listing type, form, strength and availability. Unknown filter values are ignored. Blocked, draft and unreviewed listings never appear.
- Grid/list view, mobile filter drawer (focus trap, Escape to close), all 64 districts in the location cascade
- Notification dropdown with unread count, account menu, mobile bottom navigation, confirmation dialogs, loading states on submit, local photo preview, character counters
- Dashboard and admin statistics are **counted** from records, not typed in

### What is deliberately *not* working yet (honest placeholders)
- **No form saves anything.** Every form action has its final named route but is handled by `PreviewActionController`, which redirects back with a "Preview: …" message explaining what Part 2 will do.
- **No real accounts.** Member and admin pages are reachable without logging in, shown as the sample member "Nadia Rahman" and sample admin "Kamal Hossain". A banner on every page says this is a preview.
- **Sample data** (`app/Support/SampleData.php`) is fictional. Match relevance percentages are placeholders until Part 3.
- Upazila lists are complete only for the districts used by the sample data; other districts show "<District> Sadar" until the database is seeded in Part 2.
- No email/SMS provider is configured; the UI says so wherever it matters.

## Project structure (Part 1)

```
app/Enums/                 Status enums (listing, request, verification, report, account…) with label/colour/icon
app/Support/SampleData.php Part 1 only: fictional records shaped like the future Eloquent models
app/Support/ListingSearch.php  Search + filter rules (becomes an Eloquent query in Part 2)
app/Http/Controllers/      Public, Account/*, Admin/* controllers + PreviewActionController (Part 1 only)
config/locations.php       Divisions → districts → upazilas
resources/views/components/   Design system: button, status-badge, medicine-card, form fields, dialogs, charts, layouts
resources/views/{pages,medicines,auth,account,admin,errors}/   Screens
docs/prototype/            The original static HTML prototype, kept for reference
tests/Feature/FrontendPagesTest.php
```

## Handing over to Part 2
1. Create migrations/models matching the objects in `SampleData` (same property names keep the views working).
2. Replace each `PreviewActionController` route with a real controller + Form Request + Policy.
3. Swap `SampleData::…` calls in controllers for Eloquent queries; delete `SampleData`, `PreviewActionController` and `<x-preview-banner>`.
4. Wrap member routes in `auth` and admin routes in an admin/reviewer middleware; remove the "Preview shortcuts" footer links.
