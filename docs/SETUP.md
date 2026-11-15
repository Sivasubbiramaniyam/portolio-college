# UCEN Faculty Portal — Setup and Manual Testing

This project is a plain-PHP application. It has **no Composer or framework
dependency**. Persistence is MySQL/MariaDB through repository interfaces, with a
temporary JSON provider kept as a fallback (`DATA_DRIVER=json`).

---

## 1. Requirements

- **PHP 8.1+** (PHP 8.2/8.3 recommended). The code uses `readonly` properties,
  `never` return types, first-class `match`, `str_starts_with` and named types.
- PHP extensions:
  - `session` (always bundled)
  - `json` (always bundled)
  - `fileinfo` — MIME detection for uploads
  - `mbstring` — safe filename truncation and display helpers
  - `pdo_mysql` — MySQL/MariaDB persistence
- MySQL 5.7+/MariaDB 10.2+ (or set `DATA_DRIVER=json` to run without a database).
- A writable `backend/storage/` directory (and its `uploads/` subdirectory) and
  a writable `backend/logs/` directory.
- Optional: Apache 2.4 with `mod_rewrite`, or PHP's built-in server.

Check your PHP version:

```bash
php -v
php -m
```

> **Note:** This application has been executed and verified under XAMPP
> (Apache, PHP 8.5.12, MariaDB 10.11.16). Run the syntax check and manual tests
> below on your own machine before relying on it in production.

Syntax-check every PHP file (PowerShell / bash):

```bash
find . -name "*.php" -not -path "./node_modules/*" -print0 | xargs -0 -n1 php -l
```

On Windows PowerShell:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

---

## 2. Project layout

```text
.
├── backend/
│   ├── pages/                Server-rendered pages (same public URLs)
│   │   ├── index.php         Public faculty directory
│   │   ├── login.php         Sign-in page (admin/faculty)
│   │   ├── logout.php        POST-only sign-out
│   │   ├── media.php         Unguessable-ID file delivery
│   │   ├── faculty/view.php  Public read-only profile viewer
│   │   ├── staff/            Faculty workspace + editor
│   │   └── admin/            Admin dashboard + editor
│   ├── public/
│   │   ├── index.php         Front controller (pages + JSON API)
│   │   └── pages.php         Maps URLs to backend/pages/*.php
│   ├── router.php            Built-in server router
│   ├── bootstrap.php         Autoloader + application container
│   ├── config/app.php        Paths, session, security, uploads, institution
│   ├── core/                 Request/response/router/session/CSRF/validation
│   ├── repositories/         Interfaces + temporary JSON implementations
│   ├── services/             Auth, faculty CRUD, completion, uploads
│   ├── api/v1/               JSON API controllers
│   ├── views/                Shared layout, profile renderer, form renderer
│   ├── bin/make-user.php     CLI user creation/listing
│   ├── storage/              Runtime JSON + seed + uploads (not public)
│   └── logs/                 Structured JSON-lines logs (not public)
├── frontend/
│   ├── index.html            Standalone demo UI (unchanged)
│   ├── css/, js/, assets/    Shared styles, scripts and images
│   ├── server.js             Optional Node static server for the demo
│   └── legacy/               Archived old demo folders (not used)
├── docs/                     Data contract, API and setup docs
└── .htaccess                 Apache rewrites and private-path denial
```

The legacy `frontend/legacy/faculty-profile-portal/` and `frontend/legacy/public/`
directories are not used by this application. `frontend/index.html` remains the
original demo and is untouched; the PHP pages reuse `frontend/css/style.css`,
`frontend/css/portal.css`, `frontend/js/portal.js` and `frontend/assets/`.

---

## 3. Run with PHP's built-in server

From the repository root:

```bash
php -S localhost:8000 backend/router.php
```

Then open <http://localhost:8000/>.

`backend/router.php`:

- serves real static files (CSS/JS/images/fonts/PDF),
- forwards everything else to `backend/public/index.php`,
- denies `/backend/`, `/docs/`, `/storage/`, dotfiles, `router.php`,
  `server.js`, `package.json` and `README.md`.

The front controller maps `/` and page URLs such as `/login.php` and
`/admin/dashboard.php` to `backend/pages/`, and `/api/v1/*` to the JSON API.
Public URLs are unchanged from the pre-reorganization layout.

Do **not** start the server inside `backend/` or `frontend/`; the pages resolve
paths from the repository root.

For HTTPS-only cookies in production:

```bash
SESSION_COOKIE_SECURE=true php -S localhost:8000 backend/router.php
```

The cookie flag is also set automatically when the request is HTTPS.

---

## 4. Run with Apache

1. Point the virtual host `DocumentRoot` at this repository root (not
   `backend/public`), so the PHP pages and `frontend/` assets are reachable.
2. Enable `mod_rewrite` and `AllowOverride All`.
3. The root `.htaccess`:
   - rewrites `/api/v1/*` to `backend/public/index.php`,
   - denies `/backend/`, `/docs/`, `/storage/`, dotfiles and sensitive files,
   - sends unknown paths to the directory page or `404`.
4. Ensure `AllowOverride` permits `RewriteRule`. On Debian/Ubuntu:

```apache
<Directory /var/www/ucen>
    AllowOverride All
    Require all granted
</Directory>
```

Example minimal virtual host:

```apache
<VirtualHost *:80>
    ServerName ucen.local
    DocumentRoot /var/www/ucen

    <Directory /var/www/ucen>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  ${APACHE_LOG_DIR}/ucen-error.log
    CustomLog ${APACHE_LOG_DIR}/ucen-access.log combined
</VirtualHost>
```

Set real file permissions so PHP can write storage and logs (never make the
whole repository world-writable):

```bash
mkdir -p backend/storage/uploads backend/logs
chmod -R 775 backend/storage backend/logs
chown -R www-data:www-data backend/storage backend/logs
```

On Windows/XAMPP, ensure the Apache service account can write to
`backend\storage` and `backend\logs`.

---

## 5. Persistence: MySQL (default) or JSON

### MySQL provider (default)

The default provider is MySQL/MariaDB. Create the schema and load the seed
profiles:

```bash
# Connection comes from DB_* environment variables (defaults: 127.0.0.1:3306,
# database "ucen_portal", user "root", empty password).
php backend/bin/db-migrate.php            # create database + tables
php backend/bin/db-migrate.php --fresh    # drop the tables first
php backend/bin/db-seed.php               # load the 12 seed profiles
php backend/bin/db-seed.php --from=path/to/faculty.json
```

`backend/db/schema.sql` holds the DDL: one `faculty_profiles` root table, one
child table per collection, plus `users` and `assets`. Foreign keys use
`ON DELETE CASCADE`, `faculty_id` / `email` are uniquely indexed
(case-insensitively) and a generated column enforces at most one dean. Column
names and types are documented against
[`BACKEND-DATA-CONTRACT.md`](BACKEND-DATA-CONTRACT.md).

The MySQL repositories live in `backend/repositories/Mysql*.php` and implement
the same interfaces as the JSON ones, so no page, controller, validator or view
changes. `db-seed.php` updates profiles that already exist, so it is safe to
re-run.

### JSON provider (fallback)

Set `DATA_DRIVER=json` and `AUTH_USER_PROVIDER=json` to run without a database.
On the first read, `JsonStore` initialises `backend/storage/faculty.json` from
`backend/storage/seed/faculty.json`. The seed contains **12 canonical
profiles**: 11 directory members and the dean (`fac-dean`).

### Runtime files (all ignored by Git)

| File | Purpose |
|---|---|
| `backend/storage/faculty.json` | Faculty profiles (JSON provider only) |
| `backend/storage/assets.json` | Upload metadata (JSON provider only) |
| `backend/storage/users.json` | Authentication users (JSON provider only) |
| `backend/storage/uploads/YYYY/MM/*` | Uploaded files (both providers) |
| `backend/logs/app.log` | Structured JSON-lines application log |

Do not commit `users.json`, `assets.json`, runtime `faculty.json`, uploads or
logs.

---

## 6. Create authentication users

There are **no shipped credentials** and no plaintext passwords anywhere in the
repository. Create the first account from the CLI:

```bash
php backend/bin/make-user.php --email=you@ucen.ac.in --role=admin
```

The command prompts for a password (hidden on Unix) with these rules:

- minimum 10 characters,
- at least one letter and one digit,
- hashed with Argon2id where available, otherwise bcrypt.

Create a faculty account linked to a seed profile:

```bash
php backend/bin/make-user.php \
  --email=faculty@ucen.ac.in \
  --role=faculty \
  --faculty=fac-ramanathan \
  --name="Dr. K. Ramanathan"
```

Useful options:

```bash
php backend/bin/make-user.php --list          # list users without hashes
php backend/bin/make-user.php --help
printf '%s\n' 'StrongPass123' | php backend/bin/make-user.php \
  --email=admin@ucen.ac.in --role=admin --password-stdin
```

Notes:

- `--role` is `admin`, `faculty` or `viewer` (default `faculty`).
- A faculty account **must** be linked to a unique existing profile ID.
- `--password=…` is supported for automation but is discouraged because the
  password can remain in shell history.
- Until the user store (MySQL `users` table, or `backend/storage/users.json`
  with the JSON provider) has at least one active user, sign-in returns
  `AUTH_NOT_CONFIGURED` and `login.php` disables submission.

---

## 7. Configuration

Edit `backend/config/app.php` for paths, session lifetime, upload limits,
MIME allow-lists and institution metadata. Deployment overrides are read from
environment variables:

| Variable | Default | Meaning |
|---|---|---|
| `APP_ENV` | `development` | Environment label |
| `APP_DEBUG` | `true` | Debug flag for the application |
| `SESSION_COOKIE_SECURE` | `false` | Force the `Secure` cookie flag |
| `DATA_DRIVER` | `mysql` | Persistence driver selector (`mysql` or `json`) |
| `AUTH_USER_PROVIDER` | `mysql` | User provider selector (`mysql` or `json`) |
| `DB_HOST` | `127.0.0.1` | MySQL/MariaDB host |
| `DB_PORT` | `3306` | MySQL/MariaDB port |
| `DB_DATABASE` | `ucen_portal` | Database name (created by `db-migrate.php`) |
| `DB_USERNAME` | `root` | Database user |
| `DB_PASSWORD` | `` (empty) | Database password |
| `DB_CHARSET` | `utf8mb4` | Connection charset |
| `APP_BASE_PATH` | `` | Optional sub-path prefix used by `app_url()` |

Do not commit real credentials. Passwords are only ever stored as Argon2id or
bcrypt hashes in the `users` table (or `users.json` with the JSON provider).

The repository includes [`.env.example`](../.env.example) as a reference for
these variables. It is **not** loaded automatically: export the values in your
shell, via Apache `SetEnv`, or through your service manager before starting PHP.


---

## 8. Page map

| URL | Role | Purpose |
|---|---|---|
| `/` | public | Directory, stats, filters, dean spotlight |
| `/faculty/view.php?id=fac-ramanathan` | public | Full read-only profile and completion checklist |
| `/login.php` | public | Sign in as admin or faculty |
| `/staff/workspace.php` | faculty (owner) | Own profile summary, completion, account panel |
| `/staff/edit.php` | faculty (owner) | Edit own profile with uploads |
| `/admin/dashboard.php` | admin | Accurate stats, dean spotlight, all profiles |
| `/admin/edit.php?id=…` | admin | Create/edit/delete any profile |
| `/media.php?id=asset_…` | public via unguessable ID | Serve uploaded image/PDF |
| `/logout.php` | signed-in | POST-only sign-out |

The public viewer is unauthenticated by design. Staff and admin pages require a
valid PHP session and enforce role/ownership server-side, not just in navigation.

---

## 9. Manual testing

Run `php -S localhost:8000 backend/router.php` and use a fresh browser profile (or
clear the session cookie) between roles.

### 9.1 Public viewer (no account)

1. Open `/`. Confirm the directory lists 11 profiles and the dean appears only
   in the spotlight, not in the directory cards.
2. Use the search box and department/designation filters. Confirm results
   update and the count/stat blocks stay consistent.
3. Click a faculty card. Confirm `/faculty/view.php?id=…` renders every section
   that has data, and empty sections render a friendly empty state.
4. Confirm the completion ring/checklist shows 15 sections and the percentage
   matches `round(filled / 15 * 100)`.
5. Open `/faculty/view.php?id=does-not-exist`. Confirm a clean 404 page, not a
   stack trace.
6. Confirm `/backend/`, `/docs/`, `/storage/`, `/backend/router.php` and
   `/backend/storage/users.json` return 404.
7. Try `GET /api/v1/health` and `GET /api/v1/bootstrap`; confirm the success
   envelope and that `/api/v1/auth/me` still returns a CSRF token when signed out.

### 9.2 Faculty owner

1. Create the account:
   `php backend/bin/make-user.php --email=faculty@ucen.ac.in --role=faculty --faculty=fac-ramanathan`
2. Sign in at `/login.php` with the faculty role card. Confirm redirect to
   `/staff/workspace.php`.
3. Confirm the workspace shows only the linked profile and its true completion.
4. Open `/staff/edit.php`. Change a field, add a collection entry, upload a new
   photo, and save. Confirm the success flash and updated values.
5. Submit an invalid value (blank required field, bad email, invalid year).
   Confirm inline errors, retained input and no lost collections.
6. Attempt to open `/admin/dashboard.php`. Confirm `403`/redirect — never a
   rendered admin page.
7. As the owner, open `/faculty/view.php?id=<another profile>`; viewing is
   public, but confirm the edit button is not offered for other profiles.
8. Call the API directly: `PATCH /api/v1/faculty/fac-ramanathan` succeeds with
   the session cookie and CSRF header; `PATCH` on another profile returns `403`;
   a request without `X-CSRF-Token` returns `403 CSRF_TOKEN_MISMATCH`.
9. Sign out via the POST form. Confirm the session is destroyed and protected
   pages redirect to `/login.php`.

### 9.3 Admin

1. Create the account:
   `php backend/bin/make-user.php --email=admin@ucen.ac.in --role=admin`
2. Sign in with the admin role card. Confirm redirect to
   `/admin/dashboard.php`.
3. Compare the overview numbers with `/api/v1/admin/overview`:
   - `total`/`complete`/`incomplete`/`rate` exclude the dean,
   - `totalProfiles` includes the dean,
   - department counts sum to `total`.
4. Create a profile in `/admin/edit.php` with required fields and at least one
   collection entry. Confirm it appears in the directory and that duplicate
   `facultyId` or `email` is rejected with a field error.
5. Edit an existing profile, including the dean flag and display order, then
   confirm the values persist.
6. Attempt to delete the dean. Confirm the action is blocked with a clear
   message and the profile remains.
7. Delete a non-dean profile. Confirm it disappears from the dashboard and
   directory.
8. Upload a JPEG/PNG/WebP and a PDF. Confirm they render/download through
   `/media.php?id=…`. Try an oversized file, a renamed `.exe`/text file and a
   malformed image; confirm each is rejected.
9. Sign out and confirm `/admin/*` is no longer reachable.

### 9.4 API-only checks

```bash
# Health and bootstrap
curl -i http://localhost:8000/api/v1/health
curl -i http://localhost:8000/api/v1/bootstrap

# Unknown route and wrong verb
curl -i http://localhost:8000/api/v1/nope
curl -i -X DELETE http://localhost:8000/api/v1/health

# Public list with filters
curl "http://localhost:8000/api/v1/faculty?includeDean=1&pageSize=5"
curl "http://localhost:8000/api/v1/faculty?department=Computer%20Science%20and%20Engineering"

# Completion
curl http://localhost:8000/api/v1/faculty/fac-ramanathan/completion

# Unauthenticated write must fail
curl -i -X PATCH -H "Content-Type: application/json" \
  -d '{"specialization":"AI"}' \
  http://localhost:8000/api/v1/faculty/fac-ramanathan

# Admin-only overview must reject anonymous callers
curl -i http://localhost:8000/api/v1/admin/overview
```

Expected: success responses always have `data` and `meta`; every failure has
`error.code`, `error.message` and `error.fields`.

---

## 10. Security checklist

- Session cookies: `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS.
- Session ID regenerated on sign-in; sign-out destroys the session.
- CSRF required for all writes, including login, logout, profile saves and
  uploads (`X-CSRF-Token` or `_csrf`).
- Role and ownership checks are enforced in services, not only in the UI.
- The dean profile is protected from deletion.
- Passwords are hashed with Argon2id/bcrypt; no plaintext credentials exist.
- Uploads are size-limited, MIME-sniffed, pixel/signature checked, stored with
  random names outside the public web root and served by unguessable asset ID.
- `media.php` validates the asset ID and storage key and blocks path traversal.
- All HTML output is escaped; external URLs are filtered by
  `safe_asset_url()`/`safe_external_url()`.
- API and page errors do not leak stack traces; details go to structured logs.
- Login attempts are rate-limited per session (8 failures / 5 minutes).
- `/backend/`, `/docs/`, `/storage/`, dotfiles and package/config files are
  denied by both `router.php` and `.htaccess`.

---

## 11. Troubleshooting

| Symptom | Likely cause / fix |
|---|---|
| `AUTH_NOT_CONFIGURED` on sign-in | No users yet. Run `php backend/bin/make-user.php …` |
| `503 degraded` from `/health` | `backend/storage`, `backend/storage/uploads` or `backend/logs` is not writable |
| Page loads without CSS | You are not serving the repository root; keep `/frontend/…` reachable |
| `/api/v1/...` returns 404 under Apache | `mod_rewrite`/`AllowOverride` disabled, or `.htaccess` not present |
| Login redirects back to `/login.php` | Session cookie not stored (HTTPS/Secure mismatch or cookies blocked) |
| Uploads fail with `INVALID_FILE` | More than 5 MB (media) / 3 MB (attachments), or an unsupported/corrupt file |
| Reset the demo data | Delete `backend/storage/faculty.json`; the seed is recreated on next read |
| Check errors | Read `backend/logs/app.log` (JSON lines, secrets redacted) |

---

## 12. Known limitations (intentional for this phase)

- Persistence is JSON, not MySQL. Concurrent writes are locked per file, but
  this is not a production database.
- There is no first-run admin bootstrap beyond the CLI user creator.
- Failed profile saves can leave orphaned uploads (no cleanup job yet).
- `GET /faculty`, `/faculty/{id}`, `/dean` and completion are public read
  routes even though the data contract listed them as session routes; the
  public viewer requires them to work without a session.
- `frontend/index.html` and `server.js` remain the original demo integration
  and are not used by the PHP pages.
