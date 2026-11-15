# UCEN Faculty Portal — JSON API v1

Plain-PHP JSON API implemented under `backend/api/v1/` and served by
`backend/public/index.php`.

- **Base URL:** `/api/v1`
- **Transport:** JSON over HTTP (`Content-Type: application/json`)
- **Authentication:** PHP session cookie
- **Rate limit:** 8 failed sign-ins per 5-minute session window
- **Persistence:** temporary JSON files behind repository interfaces (see
  [`SETUP.md`](SETUP.md)); MySQL is intentionally deferred.

The API is the same whether it is reached through `php -S … backend/router.php`
(built-in server) or Apache with the root `.htaccess` rewrite rules.

---

## 1. Conventions

### Success envelope

Every successful response has `data` and `meta`. `meta` is always present and
serialises as `{}` when there is nothing extra to report.

```json
{
  "data": {},
  "meta": {}
}
```

### Error envelope

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Please correct the highlighted fields.",
    "fields": {
      "email": "Enter a valid email address."
    }
  }
}
```

`fields` is an object keyed by request field path (`qualifications[0].degree`,
`email`, …). It serialises as `{}` when there are no per-field errors.

### Error codes

| Code | HTTP | Meaning |
|---|---|---|
| `VALIDATION_ERROR` | 422 | One or more fields failed validation |
| `INVALID_CREDENTIALS` | 401 | Wrong email/password, inactive account or role mismatch |
| `UNAUTHENTICATED` | 401 | No active session |
| `FORBIDDEN` | 403 | Authenticated but not allowed to perform the action |
| `NOT_FOUND` | 404 | Unknown profile, route or resource |
| `METHOD_NOT_ALLOWED` | 405 | Route exists for a different HTTP verb |
| `CONFLICT` | 409 | Dean protection, duplicate faculty ID/email, etc. |
| `INVALID_FILE` | 422 | Upload is empty, too large, wrong type or corrupt |
| `AUTH_NOT_CONFIGURED` | 503 | No users file yet; run the user creation CLI |
| `CSRF_TOKEN_MISMATCH` | 403 | Missing or stale CSRF token |
| `RATE_LIMITED` | 429 | Too many failed sign-in attempts |
| `INTERNAL_ERROR` | 500 | Unexpected server error (details are logged, not returned) |

### CSRF protection

State-changing requests (`POST`, `PUT`, `PATCH`, `DELETE`) must send the CSRF
token. The token is issued by `GET /api/v1/auth/me` as `meta.csrfToken` and is
also embedded in server-rendered pages.

Preferred header:

```http
X-CSRF-Token: <token>
```

A form field named `_csrf` is accepted for form-encoded requests. Sign-in and
sign-out also require the token. Tokens rotate on successful sign-in.

### Authentication and roles

| Role | Capabilities |
|---|---|
| `viewer` | Read-only directory/profile access (unauthenticated pages use the public read routes) |
| `faculty` | Read everything; update **only** the profile linked to `facultyProfileId`; upload files |
| `admin` | Full profile CRUD, dean flag, delete (dean profile protected), admin overview, uploads |

Session cookies are `HttpOnly`, `SameSite=Lax` and are marked `Secure`
automatically on HTTPS (or when `SESSION_COOKIE_SECURE=true`). The session ID
is regenerated on sign-in.

### PATCH vs PUT

- **`PATCH`** merges the supplied root scalars into the existing profile and
  **replaces** any supplied collection array. Omitted collections are kept.
- **`PUT`** treats the payload as authoritative for editable root fields; send
  the complete profile to avoid clearing values.

`id`, `createdAt`, `updatedAt` and `isDean` cannot be changed by non-admins.
The dean flag is admin-only.

---

## 2. Endpoints

| Method | Path | Auth | Description |
|---|---|---|---|
| `GET` | `/health` | public | Health probe |
| `GET` | `/bootstrap` | public | Institution, enums, collections, root fields |
| `POST` | `/auth/login` | public + CSRF | Sign in |
| `POST` | `/auth/logout` | session + CSRF | Sign out |
| `GET` | `/auth/me` | optional session | Current user/profile and CSRF token |
| `GET` | `/faculty` | public | List/search/filter/paginate profiles |
| `GET` | `/faculty/{id}` | public | Full profile + completion |
| `POST` | `/faculty` | admin + CSRF | Create profile |
| `PUT` | `/faculty/{id}` | admin or owner + CSRF | Full update |
| `PATCH` | `/faculty/{id}` | admin or owner + CSRF | Partial update |
| `DELETE` | `/faculty/{id}` | admin + CSRF | Delete profile (dean protected) |
| `GET` | `/faculty/{id}/completion` | public | Completion breakdown |
| `GET` | `/admin/overview` | admin | Accurate directory statistics |
| `GET` | `/dean` | public | Dean spotlight profile |
| `POST` | `/uploads` | admin or faculty + CSRF | Multipart file upload |

---

## 3. Configuration

### `GET /api/v1/health`

```json
{
  "data": {
    "status": "ok",
    "version": "1.0.0",
    "time": "2026-11-14T10:00:00+00:00",
    "checks": {
      "storage": true,
      "uploads": true,
      "logs": true,
      "authConfigured": true
    }
  },
  "meta": {}
}
```

Returns `503` with `status: "degraded"` when storage, uploads or logs are not
writable. `authConfigured` is informational and does not affect the status code.

### `GET /api/v1/bootstrap`

Returns institution metadata, departments, designations, role labels, enum
option lists, the twelve collection keys and the canonical root field list.

```json
{
  "data": {
    "institution": {
      "name": "University College of Engineering Nagercoil",
      "tamilName": "பல்கலைக்கழக பொறியியல் கல்லூரி நாகர்கோவில்",
      "affiliation": "A Constituent College of Anna University, Chennai",
      "address": "Industrial Estate, Konam, Nagercoil, Kanyakumari District, Tamil Nadu – 629 004",
      "doteCode": "4999",
      "logoUrl": "/frontend/assets/anna-university-logo.svg",
      "defaultProfileUrl": "/frontend/assets/default-profile.svg"
    },
    "departments": ["…"],
    "designations": ["…"],
    "roles": [{ "key": "admin", "label": "Admin / In-charge", "short": "Admin" }],
    "enums": { "projectStatus": ["Ongoing", "Completed"] },
    "collections": ["qualifications", "experience", "publications", "…"],
    "profileRootFields": ["id", "name", "facultyId", "…"],
    "version": "1.0.0"
  },
  "meta": { "cacheable": true }
}
```

---

## 4. Authentication

### `POST /api/v1/auth/login`

Request (JSON or form-encoded) plus `X-CSRF-Token`:

```json
{
  "email": "faculty@example.edu",
  "password": "…",
  "expectedRole": "faculty"
}
```

`expectedRole` is optional (`admin` or `faculty`). When supplied it must match
the account role; a mismatch returns the generic `INVALID_CREDENTIALS` error.

Response `200`:

```json
{
  "data": {
    "user": {
      "id": "usr_…",
      "email": "faculty@example.edu",
      "role": "faculty",
      "roleLabel": "Faculty Member",
      "facultyProfileId": "fac-ramanathan",
      "isActive": true,
      "lastLoginAt": null,
      "createdAt": "2026-11-14T09:00:00+00:00"
    },
    "profile": { "id": "fac-ramanathan", "name": "Dr. K. Ramanathan" }
  },
  "meta": {
    "csrfToken": "…"
  }
}
```

A fresh CSRF token is issued after sign-in. If no users file exists the response
is `503 AUTH_NOT_CONFIGURED`.

### `POST /api/v1/auth/logout`

Requires a session and CSRF token.

```json
{ "data": { "ok": true }, "meta": {} }
```

### `GET /api/v1/auth/me`

Works with or without a session. Always returns a CSRF token so the frontend can
start a sign-in flow.

```json
{
  "data": {
    "authenticated": true,
    "user": { "id": "usr_…", "role": "faculty", "facultyProfileId": "fac-ramanathan" },
    "profile": { "id": "fac-ramanathan" }
  },
  "meta": { "csrfToken": "…" }
}
```

When signed out, `authenticated` is `false`, `user` and `profile` are `null`,
and the token is still returned in `meta`.

---

## 5. Faculty profiles

### `GET /api/v1/faculty`

Query parameters:

| Parameter | Type | Default | Meaning |
|---|---|---|---|
| `q` | string | empty | Case-insensitive search over name, department, designation, specialization, faculty ID and email |
| `department` | string | empty | Exact department match |
| `designation` | string | empty | Exact designation match |
| `includeDean` | boolean | `false` | Include the dean profile (excluded from the public directory by default) |
| `page` | int | `1` | 1-based page number |
| `pageSize` | int | `50` | Page size, capped at `200` |

Response:

```json
{
  "data": [
    {
      "id": "fac-ramanathan",
      "name": "Dr. K. Ramanathan",
      "facultyId": "UCEN-CSE-1042",
      "department": "Computer Science and Engineering",
      "designation": "Professor & Head",
      "email": "…",
      "contact": "…",
      "isDean": false,
      "displayOrder": 1,
      "qualifications": [],
      "experience": [],
      "publications": [],
      "conferencesAttended": [],
      "conferencesConducted": [],
      "trainingPrograms": [],
      "patents": [],
      "projects": [],
      "researchAreas": [],
      "achievements": [],
      "certifications": [],
      "responsibilities": []
    }
  ],
  "meta": {
    "total": 11,
    "page": 1,
    "pageSize": 50,
    "stats": {
      "total": 11,
      "complete": 3,
      "incomplete": 8,
      "rate": 27,
      "departments": [
        { "department": "Computer Science and Engineering", "count": 4 }
      ]
    }
  }
}
```

Every array is always present, even when empty. `meta.stats` excludes the dean
from all counts.

### `GET /api/v1/faculty/{id}`

Returns the complete profile. `meta.completion` carries the 15-section
breakdown. Unknown IDs return `404 NOT_FOUND`.

### `POST /api/v1/faculty` — admin only

Creates a profile. Supply at minimum the required root fields and any
collections. Child IDs and sort order are normalised; if `id` is omitted a
stable `fac-…` identifier is generated.

```json
{
  "name": "Dr. A. Example",
  "facultyId": "UCEN-CSE-2001",
  "department": "Computer Science and Engineering",
  "designation": "Assistant Professor",
  "email": "example@ucen.ac.in",
  "contact": "+91 90000 00000",
  "specialization": "Machine learning",
  "qualifications": [
    { "degree": "Ph.D.", "specialization": "CSE", "institution": "Anna University", "year": 2020, "class": "Distinction" }
  ]
}
```

Returns `201` with the normalised profile. Duplicate faculty ID or email returns
`409 CONFLICT`; missing/invalid fields return `422 VALIDATION_ERROR`. Only one
profile may be marked `isDean: true`.

### `PUT` / `PATCH /api/v1/faculty/{id}` — admin or owner

Faculty accounts may edit only the profile whose ID equals their
`facultyProfileId`; anything else is `403 FORBIDDEN`. Admins may edit any
profile.

- `PUT` replaces the editable profile with the supplied payload.
- `PATCH` merges root scalars and replaces supplied collections; omitted
  collections are unchanged.

Server-managed fields (`createdAt`, `updatedAt`) are preserved. Non-admins
cannot change `isDean`, and the dean profile cannot be demoted accidentally by
faculty edits.

### `DELETE /api/v1/faculty/{id}` — admin only

```json
{ "data": { "id": "fac-example", "deleted": true }, "meta": {} }
```

The dean profile is protected and returns `409 CONFLICT`.

### `GET /api/v1/faculty/{id}/completion`

```json
{
  "data": {
    "profileId": "fac-ramanathan",
    "percent": 80,
    "filled": 12,
    "total": 15,
    "badge": { "label": "Looking strong", "tone": "strong" },
    "sections": [
      { "key": "personal", "label": "Personal Information", "complete": true },
      { "key": "specializations", "label": "Areas of Specialization", "complete": true },
      { "key": "qualification", "label": "Qualification", "complete": true },
      { "key": "experience", "label": "Professional Experience", "complete": false }
    ]
  },
  "meta": {}
}
```

Completion uses exactly 15 equally weighted sections and
`percent = round(filled / 15 * 100)`:

`personal`, `specializations`, `qualification`, `experience`, `publications`,
`conferencesAttended`, `conferencesConducted`, `trainingPrograms`, `patents`,
`projects`, `researchAreas`, `achievements`, `certifications`,
`responsibilities`, `researchIds`.

`qualification` maps to the `qualifications` collection. Badge thresholds are
100 (`complete`), 70 (`strong`), 40 (`progress`) and below 40 (`start`).

---

## 6. Administration

### `GET /api/v1/admin/overview` — admin only

```json
{
  "data": {
    "total": 11,
    "complete": 3,
    "incomplete": 8,
    "rate": 27,
    "departments": [
      { "department": "Computer Science and Engineering", "count": 4 },
      { "department": "Mechanical Engineering", "count": 2 }
    ],
    "totalProfiles": 12
  },
  "meta": {
    "deanProfileId": "fac-dean",
    "generatedAt": "2026-11-14T10:00:00+00:00"
  }
}
```

`total`, `complete`, `incomplete`, `rate` and `departments` exclude the dean.
`totalProfiles` is the true number of stored profiles (dean included).

### `GET /api/v1/dean`

Returns the dean profile, or `data: null` with a `meta.message` when no dean is
configured.

---

## 7. Uploads

### `POST /api/v1/uploads`

`multipart/form-data` with:

| Field | Type | Meaning |
|---|---|---|
| `file` | file | The file to store |
| `kind` | string | `photo`, `banner`, `certificate`, `sanction-letter` or `document` |
| `profileId` | string | Optional owner profile ID; ignored/overridden for faculty accounts |

Limits:

| Kind | Max size | Allowed MIME types |
|---|---|---|
| `photo`, `banner` | 5 MB | JPEG, PNG, WebP |
| `certificate`, `sanction-letter`, `document` | 3 MB | JPEG, PNG, WebP, GIF, PDF |

Files are MIME-sniffed server-side (not trusted from the client), image pixels
and PDF signatures are checked, and the file is stored under
`backend/storage/uploads/YYYY/MM/` with a random 32-hex filename. It is served
only through `/media.php?id=<asset id>`.

Response `201`:

```json
{
  "data": {
    "id": "asset_0123456789abcdef01234567",
    "ownerProfileId": "fac-ramanathan",
    "storageKey": "2026/11/0123456789abcdef0123456789abcdef.jpg",
    "url": "/media.php?id=asset_0123456789abcdef01234567",
    "mimeType": "image/jpeg",
    "byteSize": 48213,
    "originalName": "portrait.jpg",
    "kind": "photo",
    "uploadedBy": "usr_…",
    "createdAt": "2026-11-14T10:00:00+00:00"
  },
  "meta": {}
}
```

Faculty accounts may upload only against their own linked profile. Admins may
supply any existing `profileId`, or omit it for an unlinked asset. Store the
returned `url` in the corresponding profile field (`photo`, `banner`,
certificate/sanction-letter URL fields, …).

> Uploads are written before profile validation. A rejected profile save can
> therefore leave an unreferenced file on disk; there is no cleanup job yet.

---

## 8. Examples

### Sign in with curl

```bash
# 1. Get a CSRF token and cookie
curl -c cookies.txt -s http://localhost:8000/api/v1/auth/me

# 2. Sign in (copy csrfToken from the response)
curl -b cookies.txt -c cookies.txt \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: <csrfToken>" \
  -d '{"email":"faculty@example.edu","password":"…","expectedRole":"faculty"}' \
  http://localhost:8000/api/v1/auth/login
```

### Patch the signed-in faculty profile

```bash
curl -b cookies.txt \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: <csrfToken>" \
  -X PATCH \
  -d '{"publications":[{"title":"A study","journal":"Example Journal","year":2025}]}' \
  http://localhost:8000/api/v1/faculty/fac-ramanathan
```

### Browse the public directory

```bash
curl "http://localhost:8000/api/v1/faculty?department=Computer%20Science%20and%20Engineering&page=1&pageSize=20"
```

---

## 9. Fields and validation

The canonical profile shape lives in `backend/core/ProfileShape.php`; validation
rules live in `backend/services/FacultyService.php` and
`backend/core/Validator.php`. The complete field list, enum options and
per-collection definitions are in
[`BACKEND-DATA-CONTRACT.md`](BACKEND-DATA-CONTRACT.md).

Key rules enforced by the API:

- Required root fields: `name`, `facultyId`, `department`, `designation`,
  `email`, `contact`.
- `email` must be valid; `facultyId` and `email` must be unique across profiles.
- Enum fields accept only the lists returned by `/bootstrap`.
- Numeric fields (`year`, counts, amounts, `displayOrder`) must be integers;
  dates accept ISO/`YYYY-MM-DD`/`YYYY` forms.
- Collection children always receive a stable `id` and `sortOrder`.
- Unknown keys are ignored rather than persisted.
- Alias keys (`qualification`, `publication`, `project`, …) are accepted and
  expanded to their plural collection names.
