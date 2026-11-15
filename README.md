# UCEN Faculty Portal

Faculty profile creation and management portal for the **University College of
Engineering Nagercoil (UCEN)**, a constituent college of Anna University,
Chennai.

The application is implemented in **plain PHP 8.1+** — no framework, no
Composer, no build step and no JavaScript framework. It keeps the existing
UCEN visual design and assets, and adds server-rendered public, staff and admin
experiences backed by a JSON REST API.

> Persistence uses **MySQL/MariaDB** through repository interfaces, with the
> original **JSON provider kept as a fallback**. Set `DATA_DRIVER=json` and
> `AUTH_USER_PROVIDER=json` to run without a database. The schema lives in
> `backend/db/schema.sql` and is applied with `backend/bin/db-migrate.php`.

---

## Highlights

- **Public directory** with search, department/designation filters, dean
  spotlight, completion statistics and department distribution.
- **Read-only public profile viewer** with all 15 profile sections, completion
  checklist and print layout.
- **Faculty workspace** where an owner can edit only their own linked profile.
- **Admin workspace** with accurate overview statistics, full profile CRUD,
  dean protection and uploads.
- **Server-side authentication** using PHP sessions, role/ownership checks,
  CSRF protection and hashed passwords.
- **JSON API v1** with consistent success/error envelopes at `/api/v1`.
- **Twelve collections** and every root field from
  [`docs/BACKEND-DATA-CONTRACT.md`](docs/BACKEND-DATA-CONTRACT.md), with stable
  child IDs and sort order.
- **Exact 15-section completion logic**; the dean is excluded from directory
  statistics.
- **Safe uploads**: MIME sniffing, size limits, randomised names, storage
  outside the web root and delivery through `media.php` by unguessable ID.

---

## Quick start

Requirements: **PHP 8.1+** with `session`, `json`, `fileinfo`, `mbstring` and
`pdo_mysql`, plus **MySQL 5.7+ / MariaDB 10.2+** (or use the JSON fallback).

```bash
# 1. Create the database, tables and seed profiles (DB_* env vars configure it)
php backend/bin/db-migrate.php
php backend/bin/db-seed.php

# 2. Start the built-in server from the repository root
php -S localhost:8000 backend/router.php

# 3. Create the first admin account (interactive password prompt)
php backend/bin/make-user.php --role=admin --admin-id=admin --email=you@ucen.ac.in

# 4. Create a faculty account linked to a seed profile (auto-links by staff ID)
php backend/bin/make-user.php \
  --role=faculty \
  --staff-id=UCEN-CSE-1042 \
  --email=faculty@ucen.ac.in

# 5. Open the portal
#    http://localhost:8000/
```

No credentials are shipped. Passwords are hashed with Argon2id (or bcrypt) and
stored only in the `users` table (or `backend/storage/users.json` with the JSON
provider), never in version control.

`db-seed.php` loads 12 canonical profiles (11 directory members plus the dean,
`fac-dean`) from `backend/storage/seed/faculty.json` and is safe to re-run. To
run without a database, set `DATA_DRIVER=json` and `AUTH_USER_PROVIDER=json`;
the first read then seeds `backend/storage/faculty.json` from the same file.

Full instructions, Apache setup, MySQL/JSON provider details, storage
permissions, environment variables and manual role-based testing are in
[`docs/SETUP.md`](docs/SETUP.md).
API reference: [`docs/API.md`](docs/API.md).

---

## Pages

| URL | Access | Purpose |
|---|---|---|
| `/` | Public | Faculty directory, search/filters, dean spotlight, statistics |
| `/faculty/view.php?id={faculty_id}` | Public | Read-only profile with all sections and completion |
| `/staff/login.php` | Public | Staff sign-in (Staff ID + password) |
| `/staff/register.php` | Public | Staff self-registration (gated by the `validuser` whitelist) |
| `/admin/login.php` | Public | Administrator sign-in (Admin ID or email + password) |
| `/admin/register.php` | Public | Administrator self-registration (gated by the `validadmin` whitelist) |
| `/login.php` | Public | Legacy sign-in URL; redirects to `/staff/login.php` |
| `/logout.php` | Signed-in (POST) | Destroy the session |
| `/staff/workspace.php` | Faculty owner | Own profile summary and completion |
| `/staff/edit.php` | Faculty owner | Edit own profile and uploads |
| `/admin/dashboard.php` | Admin | Overview stats and all profiles |
| `/admin/edit.php?id={faculty_id}` | Admin | Create, edit or delete any profile |
| `/media.php?id={asset_id}` | Asset ID | Serve uploaded images/PDFs |

`frontend/index.html` remains the original standalone demo and is not part of
the PHP flow. The PHP pages reuse `frontend/css/style.css`,
`frontend/css/portal.css`, `frontend/js/portal.js` and `frontend/assets/`.

---

## Architecture

```text
Browser
  │
  ├── backend/pages/ (index.php, login.php, faculty/, staff/, admin/, media.php)
  │      └── backend/views/*  (shared layout, profile renderer, form renderer)
  │
  └── /api/v1/*  →  backend/public/index.php
                         │
                         ├── core/       Request, Router, Response, Session, CSRF,
                         │               Validator, ProfileShape, Enums, Database,
                         │               JsonStore
                         ├── api/v1/     Auth, Faculty, Admin, Upload, Config controllers
                         ├── services/   AuthService, FacultyService,
                         │               ProfileCompletionService, UploadService
                         ├── middleware/ Auth + role checks
                         └── repositories/
                                 interfaces
                                   ├── MysqlFacultyRepository  ← default
                                   ├── MysqlUserRepository     ← default
                                   ├── MysqlAssetRepository    ← default
                                   ├── JsonFacultyRepository   ← fallback
                                   ├── JsonUserRepository      ← fallback
                                   └── JsonAssetRepository     ← fallback
```

- `backend/bootstrap.php` provides an explicit PSR-4-style autoloader and the
  application container; no Composer is required.
- `backend/core/ProfileShape.php` is the canonical profile shape and normaliser.
- `backend/core/Database.php` is a thin PDO wrapper (prepared statements, a
  transaction helper, lazy connection).
- `backend/services/FacultyService.php` owns validation, ownership checks,
  dean protection and PATCH/PUT semantics.
- `backend/services/ProfileCompletionService.php` implements the exact
  15-section rules and directory statistics.
- `router.php` (now `backend/router.php`) and `.htaccess` deny `/backend/`,
  `/docs/`, `/storage/`, dotfiles and package/config files, and forward
  `/api/v1/*` to the API front controller.

### Success and error envelopes

```json
{ "data": {}, "meta": {} }
```

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Please correct the highlighted fields.",
    "fields": { "identifier": "Staff or admin ID is required." }
  }
}
```

### Endpoints

`GET /health`, `GET /bootstrap`, `POST /auth/login`, `POST /auth/logout`,
`GET /auth/me`, `GET /faculty`, `GET /faculty/{id}`, `POST /faculty`,
`PUT /faculty/{id}`, `PATCH /faculty/{id}`, `DELETE /faculty/{id}`,
`GET /faculty/{id}/completion`, `GET /admin/overview`, `GET /dean`,
`POST /uploads`.

See [`docs/API.md`](docs/API.md) for payloads, filters, auth rules, CSRF,
upload limits and curl examples.

---

## Roles and authorization

| Role | Access |
|---|---|
| Public / viewer | Directory, profiles, completion, dean, bootstrap |
| `faculty` | Everything public, plus edit **own** linked profile and upload |
| `admin` | All faculty capabilities for any profile, create/delete, dean flag, overview |

Authorization is enforced in the service layer from the session, not only in
the navigation. A faculty account cannot edit another profile even by calling
the API directly. The dean profile cannot be deleted.

---

## Security

- Session cookies are `HttpOnly`, `SameSite=Lax`, and `Secure` on HTTPS.
- Session IDs regenerate on sign-in; sign-out destroys the session.
- CSRF tokens protect login, logout, profile writes and uploads
  (`X-CSRF-Token` header or `_csrf` field).
- Passwords are hashed with Argon2id where available, otherwise bcrypt; no
  plaintext or invented credentials exist in the repository.
- Login attempts are rate-limited per session.
- Uploads are size-limited (5 MB media / 3 MB attachments), MIME-sniffed,
  content-checked and stored with random names outside the public web root.
- `media.php` validates asset IDs and storage keys to prevent path traversal.
- All view output is escaped; external/asset URLs pass through allow-list
  helpers.
- Errors never leak stack traces to clients; structured details go to
  `backend/logs/app.log` with secrets redacted.
- Private directories and sensitive files are denied by both `router.php` and
  `.htaccess`.

---

## Persistence: MySQL (default) and JSON (fallback)

### MySQL / MariaDB

`backend/db/schema.sql` is the DDL: a `faculty_profiles` root table, one child
table per collection, plus `users` and `assets`. Foreign keys use
`ON DELETE CASCADE`, `faculty_id` / `email` are uniquely indexed
(case-insensitively) and a generated column enforces at most one dean. Column
names and types match [`docs/BACKEND-DATA-CONTRACT.md`](docs/BACKEND-DATA-CONTRACT.md).

```bash
php backend/bin/db-migrate.php            # create database + tables
php backend/bin/db-migrate.php --fresh    # drop the tables first
php backend/bin/db-seed.php               # load the seed profiles (idempotent)
php backend/bin/db-seed.php --from=PATH   # load another {"profiles": [...]} file
```

Connection settings come from `DB_HOST`, `DB_PORT`, `DB_DATABASE`,
`DB_USERNAME`, `DB_PASSWORD` and `DB_CHARSET` (see `.env.example`). The MySQL
repositories implement the same interfaces as the JSON ones, so no page,
controller, validator or view changes when switching providers.

### JSON fallback

Runtime data lives in `backend/storage/` (ignored by Git):

| File | Contents |
|---|---|
| `faculty.json` | Profiles (seeded from `seed/faculty.json` on first read) |
| `users.json` | Authentication users with password hashes |
| `whitelist.json` | Approved staff/admin IDs for self-registration |
| `assets.json` | Upload metadata |
| `uploads/YYYY/MM/*` | Uploaded files (both providers) |

`JsonStore` uses per-file locking, which is adequate for development but is not
a production database. Select it with `DATA_DRIVER=json` and
`AUTH_USER_PROVIDER=json`.

---

## Created and modified files

### Created — application entry points

- `backend/pages/index.php` — public directory, statistics, dean spotlight
- `backend/pages/login.php` — legacy sign-in URL; redirects to the staff sign-in
- `backend/pages/logout.php` — POST-only sign-out
- `backend/pages/media.php` — guarded upload delivery
- `backend/pages/faculty/view.php` — public read-only profile viewer
- `backend/pages/staff/workspace.php` — faculty owner workspace
- `backend/pages/staff/edit.php` — faculty profile editor
- `backend/pages/admin/dashboard.php` — admin overview
- `backend/pages/admin/edit.php` — admin profile editor
- `backend/public/index.php` — single front controller (pages + API)
- `backend/public/pages.php` — page dispatcher for `backend/pages/`
- `backend/router.php` — built-in server router and private-path denial
- `.htaccess` — Apache rewrites and private-path denial

### Created — backend

- `backend/bootstrap.php`
- `backend/config/app.php`
- `backend/public/index.php`
- `backend/bin/make-user.php`
- `backend/bin/db-migrate.php` — create database + tables from `db/schema.sql`
- `backend/bin/db-seed.php` — load seed/JSON profiles into MySQL (idempotent)
- `backend/db/schema.sql` — MySQL/MariaDB DDL (15 tables)
- `backend/core/`: `Application.php`, `Config.php`, `ApiException.php`,
  `ProfileShape.php`, `Logger.php`, `Router.php`, `Response.php`,
  `Database.php`, `JsonStore.php`, `Request.php`, `helpers.php`, `Enums.php`,
  `Session.php`, `Csrf.php`, `SecurityHeaders.php`, `Validator.php`
- `backend/repositories/`: `FacultyRepositoryInterface.php`,
  `UserRepositoryInterface.php`, `AssetRepositoryInterface.php`,
  `MysqlFacultyRepository.php`, `MysqlUserRepository.php`,
  `MysqlAssetRepository.php`, `TranslatesDriverErrors.php`,
  `JsonFacultyRepository.php`, `JsonUserRepository.php`,
  `JsonAssetRepository.php`
- `backend/services/`: `AuthService.php`, `FacultyService.php`,
  `ProfileCompletionService.php`, `UploadService.php`
- `backend/middleware/`: `AuthMiddleware.php`, `RoleMiddleware.php`
- `backend/api/v1/`: `AuthController.php`, `FacultyController.php`,
  `AdminController.php`, `UploadController.php`, `ConfigController.php`
- `backend/views/`: `helpers.php`, `layout.php`, `profile_view.php`,
  `form_view.php`, `form_config.php`
- `backend/storage/seed/faculty.json` — canonical 12-profile seed

### Created — frontend additions

- `frontend/css/portal.css` — portal layout, editor, tables, responsive/print
- `frontend/js/portal.js` — sidebar, previews, dynamic collections,
  confirmations, unsaved-changes guard, toasts

### Created — documentation and configuration

- `docs/API.md` — API reference
- `docs/SETUP.md` — setup, operations and manual testing
- `.env.example` — reference for supported environment variables (not
  auto-loaded; export them in the environment)

### Modified

- `README.md` — rewritten for the PHP application
- `.gitignore` — runtime JSON, uploads, logs, env and vendor artefacts
- `package.json`, `frontend/server.js` — retained/adjusted from the earlier
  frontend-tooling phase; the PHP application does not require Node
- `frontend/index.html`, `frontend/css/style.css`, `frontend/js/script.js`,
  `frontend/assets/*` — moved from the repository root into `frontend/`
  (original demo preserved)

The original root `index.html`, `style.css`, `script.js` and `assets/*` were
moved to `frontend/`; `server.js` now lives at `frontend/server.js`. The legacy
`frontend/legacy/faculty-profile-portal/` and `frontend/legacy/public/`
directories are not used by this application.

---

## Data contract

[`docs/BACKEND-DATA-CONTRACT.md`](docs/BACKEND-DATA-CONTRACT.md) is the source
of truth for root fields, the twelve collections, enums, validation rules,
completion sections and API expectations. The implemented MySQL DDL is in
`backend/db/schema.sql`.

Completion uses exactly 15 equally weighted sections:

`personal`, `specializations`, `qualification`, `experience`, `publications`,
`conferencesAttended`, `conferencesConducted`, `trainingPrograms`, `patents`,
`projects`, `researchAreas`, `achievements`, `certifications`,
`responsibilities`, `researchIds`.

`percent = round(filled / 15 * 100)`. The dean is excluded from directory and
admin directory statistics.

---

## Testing status

Executed and verified under **XAMPP** (Apache, PHP 8.5.12, MariaDB 10.11.16):

- All 52 PHP files pass `php -l`; every page and API route returns 200 under
  Apache and the built-in server.
- The MySQL provider round-trips all 12 seed profiles with **zero differences**
  against the JSON source, including collection order and child IDs.
- A full authenticated cycle passes: login → create (nested collections) →
  read back → PATCH (collections preserved) → duplicate `facultyId`/`email`
  422 → dean delete 409 → delete → 404 → logout. Cascade delete leaves no
  orphaned child rows.
- Uploads store an asset row with the correct owner FK, `media.php` serves the
  file, and an unknown owner is rejected.

See [`docs/SETUP.md`](docs/SETUP.md) for the reproducible manual test scripts.
