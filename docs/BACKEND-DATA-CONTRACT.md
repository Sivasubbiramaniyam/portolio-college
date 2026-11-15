# UCEN Faculty Portal — Frontend → Backend Data Contract

> **Purpose:** This document is the single source of truth for everything the frontend
> (`frontend/`) needs from the backend and database. It lists every model, field,
> type, validation rule, enum, computed value, screen mapping, API endpoint, and
> database table.
>
> **Status:** The current frontend is a vanilla-JS demo that persists a single JSON
> blob in `localStorage` and optionally in `data/state.json` through `/api/state`.
> The backend contract below replaces that demo persistence.

---

## Table of contents

1. [Current architecture](#1-current-architecture)
2. [Frontend folder structure](#2-frontend-folder-structure)
3. [Screens, views and navigation](#3-screens-views-and-navigation)
4. [Roles, authentication and permissions](#4-roles-authentication-and-permissions)
5. [What the frontend needs from the backend](#5-what-the-frontend-needs-from-the-backend)
6. [API surface](#6-api-surface)
7. [Data model catalogue](#7-data-model-catalogue)
   - 7.1 [FacultyProfile (root)](#71-facultyprofile-root)
   - 7.2 [Qualification](#72-qualification)
   - 7.3 [Experience](#73-experience)
   - 7.4 [Publication](#74-publication)
   - 7.5 [FundedProject](#75-fundedproject)
   - 7.6 [ConferenceAttended](#76-conferenceattended)
   - 7.7 [ConferenceConducted](#77-conferenceconducted)
   - 7.8 [TrainingProgram](#78-trainingprogram)
   - 7.9 [Patent](#79-patent)
   - 7.10 [ResearchArea](#710-researcharea)
   - 7.11 [Achievement](#711-achievement)
   - 7.12 [Certification](#712-certification)
   - 7.13 [Responsibility](#713-responsibility)
   - 7.14 [Enums and static configuration](#714-enums-and-static-configuration)
   - 7.15 [Derived / computed models](#715-derived--computed-models)
8. [Complete example payload](#8-complete-example-payload)
9. [Database schema (PostgreSQL)](#9-database-schema-postgresql)
10. [Validation rules](#10-validation-rules)
11. [File and asset contract](#11-file-and-asset-contract)
12. [UI element → API → database mapping](#12-ui-element--api--database-mapping)
13. [Client normalisation, aliases and legacy fields](#13-client-normalisation-aliases-and-legacy-fields)
14. [End-to-end flows](#14-end-to-end-flows)
15. [Seed data](#15-seed-data)
16. [Gaps, assumptions and recommendations](#16-gaps-assumptions-and-recommendations)
17. [Backend acceptance checklist](#17-backend-acceptance-checklist)

---

## 1. Current architecture

```text
┌──────────────────────────────┐
│ frontend/index.html          │  Single page, 3 views + 3 overlays
│ frontend/css/style.css       │  Design system, responsive + print CSS
│ frontend/js/script.js        │  All state, rendering, validation, demo auth
└──────────────┬───────────────┘
               │ GET/POST /api/state   (only network call today)
               ▼
┌──────────────────────────────┐
│ server.js                    │  Static file server + demo JSON state store
│ data/state.json              │  { "roster": [ ...profiles ] }
└──────────────────────────────┘
```

**Today the frontend is the source of truth.** It seeds 11 demo profiles in
JavaScript, stores edits in `localStorage` under `ucen-faculty-portal-v2`, and mirrors
`{ roster }` to `/api/state`. Roles and passwords are hard-coded in the browser.

**Target** — the backend becomes the source of truth:

```text
Browser ──HTTPS──▶ API ──▶ PostgreSQL
   │                 │
   │                 └──▶ Object storage / uploads (photo, banner, certificates)
   └── session cookie or Bearer JWT
```

---

## 2. Frontend folder structure

```text
web/
├── frontend/
│   ├── index.html               # All screens, forms and modals
│   ├── css/
│   │   └── style.css            # Design system + print/PDF styles
│   ├── js/
│   │   └── script.js            # State, API access, rendering, validation
│   ├── assets/
│   │   ├── anna-university-logo.svg
│   │   ├── anna-university-logo.png
│   │   ├── default-profile.svg
│   │   └── default-profile.png
│   └── README.md
│
├── docs/
│   ├── BACKEND-DATA-CONTRACT.md # This file
│   └── profile.example.json     # Full example API payload
│
├── server.js                    # Local dev static server + /api/state demo
├── package.json
└── README.md
```

Static, unversioned legacy copy remains in `frontend/legacy/faculty-profile-portal/` and is not served
by `server.js`. It can be archived or deleted once the new backend is live.

### File responsibilities

| File | Responsibility | Backend relevance |
|---|---|---|
| `frontend/index.html` | Screen markup, all form inputs and their `id`s | Defines every display field |
| `frontend/css/style.css` | Visuals, responsive layout, `@media print` | None |
| `frontend/js/script.js` | Seed data, normalisation, completion calculation, rendering, demo auth, persistence | Defines the exact runtime contract |
| `server.js` | Serves `frontend/` and exposes `/api/state` | Dev-only; replace with real API |

---

## 3. Screens, views and navigation

The app is a single HTML page with three top-level views and three overlays.

| View | DOM id | Who sees it | Data shown |
|---|---|---|---|
| Dashboard | `#view-dashboard` | all roles | Hero, profile strength, admin overview, own profile strip, faculty directory, dean spotlight |
| Profile form | `#view-form` | admin + faculty | 14 form sections / 15 data sections |
| Profile view | `#view-profile` | all roles | Full 15-section academic profile |
| Preview modal | `#preview-modal` | admin + faculty | Read-only render of the form |
| Photo lightbox | `#photo-lightbox` | all | Full-size photo / banner |
| Role gate | `#role-gate` | signed-out | Role selection + admin/faculty login forms |

### Dashboard blocks

| Block | DOM id | Data needed |
|---|---|---|
| Welcome hero | `#welcome-name`, `#welcome-sub` | Current user's name/role |
| Profile strength ring | `#strength-value`, `#strength-bar-fill`, `#strength-sections`, `#strength-status` | Completion object |
| Admin stats | `#admin-total`, `#admin-complete`, `#admin-incomplete`, `#admin-rate` | Directory statistics |
| Department distribution | `#admin-departments` | Department breakdown |
| Own profile strip | `#dashboard-profile`, `#dashboard-tags`, `#dashboard-stats` | Current profile + counts |
| Faculty directory | `#directory-grid`, `#directory-count` | All non-dean profiles |
| Dean spotlight | `#dean-spotlight` | Profile whose `id` = `fac-dean` |

### Directory filters

| Control | Field | Matching |
|---|---|---|
| `#directory-search` | free text | `name`, `department`, `designation`, `specialization`, `facultyId`, `email` |
| `#directory-department` | department | exact match |
| `#directory-designation` | designation | exact match |

> Dean profiles are **excluded** from the directory grid, result count, and admin
> statistics. The dean is rendered only in the spotlight.

### Form steps (in order)

| Step | Section | Form DOM id | Collection key |
|---|---|---|---|
| 01 | Personal Information | `#form-personal` | root profile fields |
| 02 | Areas of Specialization | `#form-specializations` | `specialization` |
| 03 | Qualification | `#form-qualification` | `qualifications` |
| 04 | Professional Experience | `#form-experience` | `experience` |
| 05 | Publications | `#form-publications` | `publications` |
| 06 | Conferences Attended | `#form-conferences-attended` | `conferencesAttended` |
| 07 | Conferences Conducted | `#form-conferences-conducted` | `conferencesConducted` |
| 08 | Training Programs Conducted | `#form-training-programs-conducted` | `trainingPrograms` |
| 09 | Patents | `#form-patents` | `patents` |
| 10 | Funded Projects | `#form-projects` | `projects` |
| 11 | Research (areas, guide, scholar counts) | `#form-research` | `researchAreas` + root fields |
| 12 | Achievements & Certifications | `#form-achievements`, `#form-certifications` | `achievements`, `certifications` |
| 13 | Additional Responsibilities | `#form-responsibilities` | `responsibilities` |
| 14 | Research Profile IDs | `#form-research-ids` | root fields |

### Profile view sections (in order)

Overview hero → Personal Information → Areas of Specialization → Qualification →
Professional Experience → Publications → Conferences Attended → Conferences Conducted →
Training Programs Conducted → Patents → Funded Projects → Research → Achievements →
Certifications → Additional Responsibilities → Research Profile IDs.

---

## 4. Roles, authentication and permissions

### Roles

| Role key | UI label | Capabilities today (frontend) |
|---|---|---|
| `admin` | Admin / In-charge | View all, create faculty, edit any profile, see admin overview and department stats |
| `faculty` | Faculty Member | View directory, create/edit **own** profile only |
| `viewer` | General Viewer | Read-only directory and profiles |

### Demo credentials (must move server-side)

| Role | Email | Password | Notes |
|---|---|---|---|
| Admin | `admin@ucen.ac.in` | `admin123` | Hard-coded |
| Admin / Dean | `dean@ucen.ac.in` | `dean123` | Also owns the dean profile |
| Faculty | any faculty email | `faculty123` | Hard-coded shared password |

### Backend requirements

- Store passwords as Argon2id/bcrypt hashes — never in the frontend.
- Use an HttpOnly, Secure, SameSite cookie session, or short-lived JWT + refresh token.
- Enforce authorisation on the server:
  - `admin` → all profile writes.
  - `faculty` → only `faculty_profiles.id == session.faculty_profile_id`.
  - `viewer` → no writes.
- Never trust `role`, `activeFacultyId`, or `session` values from the browser.
- The frontend stores a local session flag; it must be replaced by a `/auth/me` check.

### Suggested `User` model

| Field | Type | Rules |
|---|---|---|
| `id` | UUID | PK |
| `email` | string | unique, valid email, lower-case |
| `passwordHash` | string | never returned by API |
| `role` | enum | `admin` \| `faculty` \| `viewer` |
| `facultyProfileId` | UUID / null | required for `faculty`; null for pure admins/viewers |
| `isActive` | boolean | default true |
| `lastLoginAt` | timestamp | nullable |
| `createdAt`, `updatedAt` | timestamp | audit |

---

## 5. What the frontend needs from the backend

At minimum, the backend must return:

1. **Bootstrap/config** — departments, designations, role labels, institution metadata,
   and all enum option lists so the form can be driven by the server.
2. **Session** — current user, role, and linked faculty profile.
3. **Profile roster** — every faculty profile with all root fields and all 12 nested
   collections (this alone renders the dashboard, directory, profile view, and form).
4. **Directory statistics** — total, complete, incomplete, rate, and department counts.
   Can be computed client-side from the roster, but should be server-provided for scale.
5. **Dean profile** — a record with `id = "fac-dean"` (or an `isDean` flag) so the
   spotlight can render.
6. **Upload endpoints** — for photos, banners, and certificate/sanction-letter files.

Everything else (completion %, counts, tags, formatting) is currently computed in the
browser. A backend may return it as a convenience, but it is not mandatory.

---

## 6. API surface

### 6.1 Current integration (works with today's frontend, unchanged)

The only network call in `frontend/js/script.js` is:

```http
GET /api/state
```

Response — must contain a `roster` array; anything else is ignored:

```json
{
  "roster": [ /* FacultyProfile[] */ ]
}
```

```http
POST /api/state
Content-Type: application/json

{ "roster": [ /* FacultyProfile[] */ ] }
```

Response: `{ "ok": true }`. If `GET` returns `null`, empty, or an object without a
non-empty `roster`, the frontend falls back to `localStorage` and then seed data.

> This is the quickest way to plug a real backend in without touching the frontend.
> It is not a suitable production API (no auth, whole-dataset writes, no concurrency).

### 6.2 Recommended v1 REST API

Base path: `/api/v1`. All responses JSON; errors in a consistent envelope.

```json
// Success
{ "data": { }, "meta": { } }

// Error
{ "error": { "code": "VALIDATION_ERROR", "message": "…", "fields": { "email": "Invalid" } } }
```

| Method | Path | Auth | Purpose | Response |
|---|---|---|---|---|
| `GET` | `/bootstrap` | public | Config + enums + institution info | `BootstrapConfig` |
| `POST` | `/auth/login` | public | Sign in | `Session` + cookie |
| `POST` | `/auth/logout` | session | Sign out | `{ ok: true }` |
| `GET` | `/auth/me` | session | Current session/user | `Session` |
| `GET` | `/faculty` | session | Directory list (search/filter/paginate) | `{ items, total, page, pageSize }` |
| `GET` | `/faculty/:id` | session | Full profile | `FacultyProfile` |
| `POST` | `/faculty` | admin | Create profile | `FacultyProfile` (201) |
| `PUT` | `/faculty/:id` | admin or owner | Full update | `FacultyProfile` |
| `PATCH` | `/faculty/:id` | admin or owner | Partial update | `FacultyProfile` |
| `DELETE` | `/faculty/:id` | admin | Delete profile (soft delete recommended) | `204` |
| `GET` | `/faculty/:id/completion` | session | Completion breakdown | `Completion` |
| `GET` | `/admin/overview` | admin | Stats + department breakdown | `AdminOverview` |
| `GET` | `/dean` | session | Dean profile/spotlight | `FacultyProfile` |
| `POST` | `/uploads` | admin or owner | Multipart file upload | `{ url, mimeType, size }` |
| `GET` | `/health` | public | Health probe | `{ status: "ok" }` |

#### Query parameters for `GET /faculty`

| Parameter | Type | Meaning |
|---|---|---|
| `q` | string | Search across `name`, `department`, `designation`, `specialization`, `facultyId`, `email` |
| `department` | string | Exact department |
| `designation` | string | Exact designation |
| `includeDean` | boolean | Default `false`; frontend excludes dean from the directory |
| `page`, `pageSize` | integer | Pagination (optional today) |

> Today the frontend filters the roster in memory, so an unfiltered roster is acceptable
> initially. Use the query parameters once the roster grows.

### 6.3 Endpoint payloads

#### `GET /bootstrap`

```json
{
  "data": {
    "institution": {
      "name": "University College of Engineering Nagercoil",
      "tamilName": "பல்கலைக்கழக பொறியியல் கல்லூரி நாகர்கோவில்",
      "affiliation": "A Constituent College of Anna University, Chennai",
      "address": "Industrial Estate, Konam, Nagercoil, Kanyakumari District, Tamil Nadu – 629 004",
      "doteCode": "4999",
      "logoUrl": "/assets/anna-university-logo.svg"
    },
    "departments": ["…"],
    "designations": ["…"],
    "roles": [
      { "key": "admin", "label": "Admin / In-charge", "short": "Admin" },
      { "key": "faculty", "label": "Faculty Member", "short": "Faculty" },
      { "key": "viewer", "label": "General Viewer", "short": "Viewer" }
    ],
    "enums": {
      "qualificationClass": ["Distinction", "1st Class", "2nd Class", "3rd Class", "Pass"],
      "projectCategory": ["Government Funded", "University Funded", "Industry Funded", "Research Grant", "Other"],
      "projectStatus": ["Ongoing", "Completed"],
      "publicationType": ["Journal Article", "Conference Paper", "Book", "Book Chapter", "Review Article", "Other"],
      "achievementCategory": ["Academic Achievement", "Teaching Achievement", "Research Achievement", "Award", "Professional Certification", "Training", "Other"],
      "certificationCategory": ["Professional Certification", "Training", "Academic Achievement", "Teaching Achievement", "Research Achievement", "Award", "Other"],
      "conferenceType": ["Technical Conference", "Research Conference", "Workshop", "Symposium", "Seminar"],
      "conferenceCategory": ["National", "International"],
      "conferenceParticipation": ["Participant", "Presenter", "Paper Presenter", "Resource Person", "Invited Speaker"],
      "conferenceRole": ["Convener", "Co-Convener", "Coordinator", "Organizing Secretary", "Organizing Committee Member", "Chairperson"],
      "trainingType": ["FDP", "Workshop", "Seminar", "Training", "STTP", "Refresher Course", "Orientation Programme", "Other"],
      "trainingLevel": ["National", "International", "State", "Institutional"],
      "trainingRole": ["Coordinator", "Resource Person", "Trainer", "Organizer"],
      "patentStatus": ["Granted", "Awarded"],
      "responsibilityCategory": ["Academic Responsibility", "Administrative Responsibility", "Department Responsibility", "Institutional Responsibility", "Professional Responsibility", "Other"],
      "responsibilityLevel": ["Department Level", "College Level", "University Level", "National Level", "International Level"],
      "researchGuideStatus": ["Recognized", "Provisional", "Expired", "Not Applicable"]
    }
  }
}
```

#### `POST /auth/login`

```json
{ "identifier": "UCEN-CSE-1042", "password": "…", "expectedRole": "faculty" }
```

`identifier` is the staff ID on the staff portal, or the admin ID / email on the
administrator portal (`email` remains accepted as an alias).

Response:

```json
{
  "data": {
    "user": { "id": "uuid", "email": "…", "staffId": "UCEN-CSE-1042", "adminId": null, "role": "faculty", "facultyProfileId": "fac-ramanathan" },
    "profile": { /* FacultyProfile */ }
  }
}
```

#### `GET /admin/overview`

```json
{
  "data": {
    "total": 10,
    "complete": 4,
    "incomplete": 6,
    "rate": 40,
    "departments": [
      { "department": "Computer Science and Engineering", "count": 3 }
    ]
  }
}
```

---

## 7. Data model catalogue

### 7.1 FacultyProfile (root)

One row in `faculty_profiles`, plus one-to-many child collections.

| JSON key | Type | Req. | DB column / type | Rules | Displayed in |
|---|---|---|---|---|---|
| `id` | string | yes | `id text pk` | Stable unique id; seeds use `fac-ramanathan`, dean is `fac-dean` | Everywhere, links |
| `name` | string | yes | `name text` | 1–120 chars; title allowed (`Dr.`) | Directory card, hero, profile, dean card |
| `facultyId` | string | yes | `faculty_id text unique` | Employee ID, 1–40 chars | Personal info, directory search, dashboard tags |
| `department` | enum | yes | `department text` | One of 8 departments | Cards, filters, admin breakdown |
| `designation` | enum | yes | `designation text` | One of 10 designations | Cards, filters, profile |
| `email` | string | yes | `email citext unique` | Valid email; faculty login identity | Contact rows, personal info |
| `contact` | string | yes | `contact text` | 7–15 digits; formatting allowed | Contact rows, personal info |
| `specialization` | string | yes | `specialization text` | Comma-separated list | Tag clouds, cards, search |
| `photo` | URL/base64 | no | `photo_url text` | JPG/PNG/WebP ≤5 MB; client crops 480×480 JPEG q0.86 | Avatars, lightbox |
| `banner` | URL/base64 | no | `banner_url text` | JPG/PNG/WebP ≤5 MB; client crops 1200×320 JPEG q0.82 | Profile hero banner |
| `researchGuideNumber` | string | no | `research_guide_number text` | ≤120 chars | Research section |
| `researchGuideUniversity` | string | no | `research_guide_university text` | ≤160 chars | Research section |
| `researchGuideDate` | date `YYYY-MM-DD` | no | `research_guide_date date` | Valid date | Research section (formatted) |
| `researchGuideStatus` | enum | no | `research_guide_status text` | `Recognized`, `Provisional`, `Expired`, `Not Applicable` | Research section |
| `phdCompleted` | integer | no | `phd_completed smallint` | ≥0 | Research stat card |
| `mphilCompleted` | integer | no | `mphil_completed smallint` | ≥0 | Research stat card |
| `otherCompleted` | integer | no | `other_completed smallint` | ≥0 | Research stat card |
| `phdGuiding` | integer | no | `phd_guiding smallint` | ≥0 | Research stat card |
| `mphilGuiding` | integer | no | `mphil_guiding smallint` | ≥0 | Research stat card |
| `otherGuiding` | integer | no | `other_guiding smallint` | ≥0 | Research stat card |
| `googleScholarId` | string | no | `google_scholar_id text` | ≤120 chars | Research Profile IDs |
| `googleScholarUrl` | URL | no | `google_scholar_url text` | `https://` URL | Research Profile IDs |
| `orcidId` | string | no | `orcid_id text` | `0000-0000-0000-0000` format ideally | Research Profile IDs |
| `orcidUrl` | URL | no | `orcid_url text` | `https://` URL | Research Profile IDs |
| `scopusId` | string | no | `scopus_id text` | Numeric string | Research Profile IDs |
| `scopusUrl` | URL | no | `scopus_url text` | `https://` URL | Research Profile IDs |
| `isDean` | boolean | rec. | `is_dean boolean` | Exactly one true | Dean spotlight; exclusion from directory |
| `displayOrder` | integer | no | `display_order int` | Directory ordering | Directory |
| `createdAt` | timestamp | auto | `created_at timestamptz` | Server-set | Audit |
| `updatedAt` | timestamp | auto | `updated_at timestamptz` | Server-set | Audit |

**Serialisation note:** the current JavaScript stores every scalar as a string
(`String(value)`), and also accepts numbers and booleans (`String()` conversion is
applied). Returning real JSON numbers/booleans is safe, but if you want byte-for-byte
parity with the demo, return strings for `year`, `amount`, `participants`, and the
scholar counts, and `"true"`/`""` for `responsibility.current`.

**Root collections** (each is an array; field details below):

| JSON key (canonical) | Accepted alias | DB table | Form step | Profile section |
|---|---|---|---|---|
| `qualifications` | `qualification` | `faculty_qualifications` | 03 | Qualification |
| `experience` | `experience` | `faculty_experiences` | 04 | Professional Experience |
| `publications` | `publication` | `faculty_publications` | 05 | Publications |
| `conferencesAttended` | `conferenceAttended` | `faculty_conferences_attended` | 06 | Conferences Attended |
| `conferencesConducted` | `conferenceConducted` | `faculty_conferences_conducted` | 07 | Conferences Conducted |
| `trainingPrograms` | `trainingProgram` | `faculty_training_programs` | 08 | Training Programs Conducted |
| `patents` | `patent` | `faculty_patents` | 09 | Patents |
| `projects` | `project` | `faculty_projects` | 10 | Funded Projects |
| `researchAreas` | `researchArea` | `faculty_research_areas` | 11 | Research |
| `achievements` | `achievement` | `faculty_achievements` | 12 | Achievements |
| `certifications` | `certification` | `faculty_certifications` | 12 | Certifications |
| `responsibilities` | `responsibility` | `faculty_responsibilities` | 13 | Additional Responsibilities |

All child rows should also expose:

| Field | Type | Notes |
|---|---|---|
| `id` | string/UUID | Stable child id (not currently displayed, but needed for edits) |
| `sortOrder` | integer | Preserve user-entered order (the UI renders arrays in order) |

> The frontend does not require `id`/`sortOrder` on children today, but adding them is
> strongly recommended for reliable updates and ordering.

---

### 7.2 Qualification

`qualifications[]` — form id prefix `qualification-`, container `#entry-list-qualification`.

| Field | Type | Req. | DB column | Rules / options | Displayed as |
|---|---|---|---|---|---|
| `degree` | string | yes | `degree text` | ≤180 chars | Timeline title |
| `institution` | string | yes | `institution text` | ≤180 chars | Timeline subtitle |
| `university` | string | no | `university text` | ≤180 chars | Meta line |
| `year` | integer/string | yes | `year smallint` | 1950 … current year + 1 | Timeline year badge |
| `classObtained` | enum | no | `class_obtained text` | `Distinction`, `1st Class`, `2nd Class`, `3rd Class`, `Pass` | Meta line |
| `specialization` | string | no | `specialization text` | ≤180 chars | Meta line |

---

### 7.3 Experience

`experience[]` — container `#entry-list-experience`.

| Field | Type | Req. | DB column | Rules | Displayed as |
|---|---|---|---|---|---|
| `institution` | string | yes | `institution text` | ≤180 chars | Timeline subtitle |
| `designation` | string | yes | `designation text` | ≤120 chars | Timeline title (fallback) |
| `from` | month `YYYY-MM` | yes | `from_month date` | Valid month | Date range |
| `to` | month `YYYY-MM` | no | `to_month date` | Empty = present | Date range end |
| `total` | string | no | `total_experience text` | Free text, e.g. `6 years 4 months` | `Total experience:` line |

> Demo seed data contains `description` on experience entries. The form does not have
> the field and the view does not render it — it is ignored. Add it only if the UI is
> extended.

---

### 7.4 Publication

`publications[]` — container `#entry-list-publication`.

| Field | Type | Req. | DB column | Rules / options | Displayed as |
|---|---|---|---|---|---|
| `title` | string | yes | `title text` | ≤300 chars | Card title |
| `type` | enum | yes | `type text` | `Journal Article`, `Conference Paper`, `Book`, `Book Chapter`, `Review Article`, `Other` | Type chip |
| `venue` | string | yes | `venue text` | Journal/conference name, ≤250 chars | Venue line |
| `authors` | string | no | `authors text` | Comma-separated names | Author line |
| `year` | integer/string | yes | `year smallint` | 1950 … current year + 1 | Year badge |
| `volume` | string | no | `volume text` | ≤40 chars | `Vol. x` chip |
| `issue` | string | no | `issue text` | ≤40 chars | `Issue x` chip |
| `pages` | string | no | `pages text` | ≤60 chars | `pp. x` chip |
| `publisher` | string | no | `publisher text` | ≤160 chars | Publisher chip |
| `indexing` | string | no | `indexing text` | e.g. `Scopus, Web of Science, UGC CARE` | Indexing chip |
| `doi` | string | no | `doi text` | DOI or URL; bare DOI is prefixed with `https://doi.org/` | DOI link |
| `url` | URL | no | `url text` | `https://` URL | "View publication" link |

Normalisation rules applied by the client:

- If `type` is missing, it is inferred from `venue`/`title` containing
  `conference|proceedings|workshop|symposium` → `Conference Paper`, else `Journal Article`.
- `Patent` is coerced to `Other`.
- Legacy `link`: if `doi` and `url` are empty, a DOI-looking link becomes `doi`,
  otherwise it becomes `url`.

---

### 7.5 FundedProject

`projects[]` — container `#entry-list-project`.

| Field | Type | Req. | DB column | Rules / options | Displayed as |
|---|---|---|---|---|---|
| `title` | string | yes | `title text` | ≤300 chars | Card title |
| `agency` | string | yes | `agency text` | ≤200 chars | Agency chip |
| `category` | enum | no | `category text` | `Government Funded`, `University Funded`, `Industry Funded`, `Research Grant`, `Other` | Category chip |
| `pi` | string | no | `principal_investigator text` | ≤180 chars | `PI:` line |
| `coPi` | string | no | `co_principal_investigator text` | ≤180 chars | `Co-PI:` line |
| `role` | string | no | `faculty_role text` | e.g. Principal Investigator, Co-Investigator | Role chip |
| `duration` | string | no | `duration text` | Free text, e.g. `2022 – 2025`; fallback when dates absent | Date line |
| `start` | month `YYYY-MM` | no | `start_month date` | Valid month | Date span |
| `end` | month `YYYY-MM` | no | `end_month date` | Valid month | Date span |
| `amount` | integer/string | no | `amount numeric(14,2)` | ≥0; formatted as INR (`₹`) | Project amount |
| `status` | enum | yes | `status text` | `Ongoing`, `Completed` (`Approved`/`Proposed` normalised to `Ongoing`) | Status pill |
| `reference` | string | no | `reference text` | Sanction/reference number | Reference line |
| `description` | string | no | `description text` | Long text | Description paragraph |
| `sanctionLetter` | file | no | `sanction_letter_url text` | Image/PDF ≤3 MB | Attachment chip |

---

### 7.6 ConferenceAttended

`conferencesAttended[]` — container `#entry-list-conference-attended`.

| Field | Type | Req. | DB column | Rules / options | Displayed as |
|---|---|---|---|---|---|
| `name` | string | yes | `name text` | ≤300 chars | Card title |
| `type` | enum | no | `type text` | `Technical Conference`, `Research Conference`, `Workshop`, `Symposium`, `Seminar` | Type chip |
| `category` | enum | no | `category text` | `National`, `International` | Status pill |
| `organizer` | string | no | `organizer text` | ≤200 chars | Organizer chip |
| `location` | string | no | `location text` | ≤160 chars | Location chip |
| `start` | date `YYYY-MM-DD` | no | `start_date date` | Valid date | Date span |
| `end` | date `YYYY-MM-DD` | no | `end_date date` | Valid date | Date span |
| `participation` | enum | no | `participation text` | `Participant`, `Presenter`, `Paper Presenter`, `Resource Person`, `Invited Speaker` | Highlight line |
| `paperTitle` | string | no | `paper_title text` | ≤300 chars | Paper line |
| `certificate` | file | no | `certificate_url text` | Image/PDF ≤3 MB | Attachment chip |

---

### 7.7 ConferenceConducted

`conferencesConducted[]` — container `#entry-list-conference-conducted`.

| Field | Type | Req. | DB column | Rules / options | Displayed as |
|---|---|---|---|---|---|
| `name` | string | yes | `name text` | ≤300 chars | Card title |
| `type` | enum | no | `type text` | Same conference types as above | Type chip |
| `category` | enum | no | `category text` | `National`, `International` | Status pill |
| `organizer` | string | no | `organizer text` | ≤200 chars | Organizer chip |
| `role` | enum | no | `faculty_role text` | `Convener`, `Co-Convener`, `Coordinator`, `Organizing Secretary`, `Organizing Committee Member`, `Chairperson` | Highlight line |
| `location` | string | no | `location text` | ≤160 chars | Location chip |
| `start` | date | no | `start_date date` | Valid date | Date span |
| `end` | date | no | `end_date date` | Valid date | Date span |
| `participants` | integer | no | `participants int` | ≥0 | `N participants` |
| `website` | URL | no | `website text` | `https://` URL | Conference website link |

---

### 7.8 TrainingProgram

`trainingPrograms[]` — container `#entry-list-training-program`.

| Field | Type | Req. | DB column | Rules / options | Displayed as |
|---|---|---|---|---|---|
| `title` | string | yes | `title text` | ≤300 chars | Card title |
| `type` | enum | no | `type text` | `FDP`, `Workshop`, `Seminar`, `Training`, `STTP`, `Refresher Course`, `Orientation Programme`, `Other` | Type chip |
| `organizer` | string | no | `organizer text` | ≤200 chars | Organizer chip |
| `level` | enum | no | `level text` | `National`, `International`, `State`, `Institutional` | Status pill |
| `start` | date | no | `start_date date` | Valid date | Date span |
| `end` | date | no | `end_date date` | Valid date | Date span |
| `role` | enum | no | `faculty_role text` | `Coordinator`, `Resource Person`, `Trainer`, `Organizer` | Highlight line |
| `participants` | integer | no | `participants int` | ≥0 | `N participants` |
| `certificate` | file | no | `certificate_url text` | Image/PDF ≤3 MB | Attachment chip |

---

### 7.9 Patent

`patents[]` — container `#entry-list-patent`.

> **Display rule:** only patents whose `status` is `Granted` or `Awarded` are rendered.
> All others exist in the database but are hidden from the profile.

| Field | Type | Req. | DB column | Rules / options | Displayed as |
|---|---|---|---|---|---|
| `title` | string | yes | `title text` | ≤300 chars | Card title |
| `number` | string | yes | `patent_number text` | ≤80 chars | Number chip |
| `type` | string | no | `patent_type text` | e.g. `Utility Patent` | Type chip |
| `inventors` | string | no | `inventors text` | Comma-separated | Author line |
| `authority` | string | no | `authority text` | ≤200 chars | Authority line |
| `country` | string | no | `country text` | ≤80 chars | Country chip |
| `grantDate` | date | yes | `grant_date date` | Valid date | `Granted <date>` |
| `status` | enum | yes | `status text` | `Granted`, `Awarded` | Status pill |
| `url` | URL | no | `url text` | `https://` URL | View patent link |
| `certificate` | file | no | `certificate_url text` | Image/PDF ≤3 MB | Attachment chip |

---

### 7.10 ResearchArea

`researchAreas[]` — container `#entry-list-research-area`.

| Field | Type | Req. | DB column | Rules | Displayed as |
|---|---|---|---|---|---|
| `area` | string | yes | `area text` | ≤160 chars | Area tag |
| `subject` | string | no | `subject text` | ≤160 chars | Subject tag |
| `specialization` | string | no | `specialization text` | ≤200 chars | Specialisation tag |
| `keywords` | string | no | `keywords text` | Comma-separated | One keyword tag each |

The Research section also renders these **root** fields:

- Research guide: `researchGuideNumber`, `researchGuideUniversity`,
  `researchGuideDate`, `researchGuideStatus`.
- Scholars completed: `phdCompleted`, `mphilCompleted`, `otherCompleted`.
- Currently guiding: `phdGuiding`, `mphilGuiding`, `otherGuiding`.

---

### 7.11 Achievement

`achievements[]` — container `#entry-list-achievement`.

| Field | Type | Req. | DB column | Rules / options | Displayed as |
|---|---|---|---|---|---|
| `title` | string | yes | `title text` | ≤300 chars | Card title |
| `category` | enum | no | `category text` | `Academic Achievement`, `Teaching Achievement`, `Research Achievement`, `Award`, `Professional Certification`, `Training`, `Other` | Category chip |
| `organization` | string | yes | `organization text` | ≤200 chars | Organisation line |
| `year` | string | no | `year text` | Year or free date | Year |
| `certificateNumber` | string | no | `certificate_number text` | ≤120 chars | Certificate number |
| `certificateUrl` | URL | no | `certificate_url text` | `https://` URL | View certificate link |
| `description` | string | no | `description text` | Long text | Paragraph |
| `certificate` | file | no | `certificate_file_url text` | Image/PDF ≤3 MB | Attachment chip |

> Note the two different fields: `certificateUrl` is an external link;
> `certificate` is an uploaded file. Both can be present.

---

### 7.12 Certification

`certifications[]` — container `#entry-list-certification`. Same shape as Achievement.

| Field | Type | Req. | DB column | Rules / options | Displayed as |
|---|---|---|---|---|---|
| `title` | string | yes | `title text` | ≤300 chars | Card title |
| `category` | enum | no | `category text` | Same options as Achievement | Category chip |
| `organization` | string | yes | `organization text` | ≤200 chars | Organisation line |
| `year` | string | no | `year text` | Year or free date | Year |
| `certificateNumber` | string | no | `certificate_number text` | ≤120 chars | Certificate number |
| `certificateUrl` | URL | no | `certificate_url text` | `https://` URL | External link |
| `description` | string | no | `description text` | Long text | Paragraph |
| `certificate` | file | no | `certificate_file_url text` | Image/PDF ≤3 MB | Attachment chip |

---

### 7.13 Responsibility

`responsibilities[]` — container `#entry-list-responsibility`.

| Field | Type | Req. | DB column | Rules / options | Displayed as |
|---|---|---|---|---|---|
| `title` | string | yes | `title text` | ≤300 chars | Timeline title |
| `category` | enum | no | `category text` | `Academic Responsibility`, `Administrative Responsibility`, `Department Responsibility`, `Institutional Responsibility`, `Professional Responsibility`, `Other` | Category chip |
| `institution` | string | no | `institution text` | ≤200 chars | Subtitle |
| `position` | string | no | `position text` | ≤160 chars | Subtitle |
| `level` | enum | no | `level text` | `Department Level`, `College Level`, `University Level`, `National Level`, `International Level` | Level chip |
| `from` | month `YYYY-MM` | no | `from_month date` | Valid month | Date span start |
| `to` | month `YYYY-MM` | no | `to_month date` | Ignored when `current` is true | Date span end |
| `current` | boolean/string | no | `is_current boolean` | Accept `true`, `"true"`, `1`, `"yes"`, `"on"` | "Currently holding" pill |

---

### 7.14 Enums and static configuration

#### Departments (8) — also used in filters and admin breakdown

1. Computer Science and Engineering
2. Information Technology
3. Electronics and Communication Engineering
4. Electrical and Electronics Engineering
5. Mechanical Engineering
6. Civil Engineering
7. MBA
8. Science and Humanities

Short codes (client-side map): `CSE`, `IT`, `ECE`, `EEE`, `MECH`, `CIVIL`, `MBA`, `S&H`.

#### Designations (10)

1. Professor & Head
2. Professor
3. Dean & Professor
4. Associate Professor
5. Asst Professor (Sr.Gr)
6. Asst Professor (Sr.Gr) & HOD
7. Assistant Professor & HOD
8. Assistant Professor
9. Dean
10. Visiting Faculty

#### Role labels

| Key | Label | Short |
|---|---|---|
| `admin` | Admin / In-charge | Admin |
| `faculty` | Faculty Member | Faculty |
| `viewer` | General Viewer | Viewer |

#### Institution metadata (currently hard-coded in HTML/JS)

| Key | Value |
|---|---|
| Institution | University College of Engineering Nagercoil |
| Tamil name | பல்கலைக்கழக பொறியியல் கல்லூரி நாகர்கோவில் |
| Affiliation | A Constituent College of Anna University, Chennai |
| Address | Industrial Estate, Konam, Nagercoil, Kanyakumari District, Tamil Nadu – 629 004 |
| DOTE code | 4999 |
| Primary colour | `#7B5EF0` |
| Dean id | `fac-dean` |
| Dean email | `dean@ucen.ac.in` |

#### Other enum lists

All form option lists are listed in the table in section 6.3 and in each model above.
Serve them from `/bootstrap` if you want to change them without a frontend deploy.

---

### 7.15 Derived / computed models

These are computed in `frontend/js/script.js`. The backend may return them, but if it
does, it must match these definitions exactly.

#### Completion

Computed over **15 sections**, equal weight (1/15 each):

```json
{
  "percent": 73,
  "filled": 11,
  "total": 15,
  "sections": [
    { "key": "personal", "label": "Personal Information", "complete": true },
    { "key": "specializations", "label": "Areas of Specialization", "complete": true },
    { "key": "qualification", "label": "Qualification", "complete": true },
    { "key": "experience", "label": "Professional Experience", "complete": true },
    { "key": "publications", "label": "Publications", "complete": true },
    { "key": "conferencesAttended", "label": "Conferences Attended", "complete": false },
    { "key": "conferencesConducted", "label": "Conferences Conducted", "complete": false },
    { "key": "trainingPrograms", "label": "Training Programs Conducted", "complete": false },
    { "key": "patents", "label": "Patents", "complete": true },
    { "key": "projects", "label": "Funded Projects", "complete": true },
    { "key": "researchAreas", "label": "Research", "complete": true },
    { "key": "achievements", "label": "Achievements", "complete": true },
    { "key": "certifications", "label": "Certifications", "complete": true },
    { "key": "responsibilities", "label": "Additional Responsibilities", "complete": true },
    { "key": "researchIds", "label": "Research Profile IDs", "complete": true }
  ]
}
```

Rules:

| Section | Complete when |
|---|---|
| `personal` | `name`, `facultyId`, `department`, `designation`, `email`, `contact` all non-empty |
| `specializations` | `specialization` non-empty |
| `researchIds` | **any** of the 6 Google Scholar / ORCID / Scopus id/url fields is non-empty |
| `researchAreas` | at least one research area **or** any research-guide field **or** any scholar-count field |
| all others | at least one child entry with at least one non-empty field |

`percent = round(filled / 15 × 100)`.

Badges: `>=100` "Profile complete", `>=70` "Looking strong", `>=40` "In progress",
otherwise "Getting started".

#### Directory statistics

```json
{ "total": 10, "complete": 4, "incomplete": 6, "rate": 40 }
```

- Excludes the dean profile.
- `complete` counts profiles whose completion is exactly 100%.
- `rate = round(complete / total × 100)`; `0` when total is 0.

#### Department breakdown

```json
[
  { "department": "Computer Science and Engineering", "count": 3 },
  { "department": "Information Technology", "count": 2 }
]
```

- Excludes the dean.
- Sorted by count descending, then department name ascending.
- Missing department is grouped as `"Not assigned"`.

#### Card / strip counters

| UI label | Source |
|---|---|
| Degrees | `qualifications.length` of non-empty entries |
| Roles | `experience.length` |
| Projects | `projects.length` |
| Papers | `publications.length` |
| Directory footer | `publications.length` + ` · ` + `projects.length` |

#### Tag lists

- `specialization` is split on commas, trimmed, and empty parts dropped.
- Dashboard: first 3; directory card: first 2; profile hero: first 5; full section: all.

---

## 8. Complete example payload

A full, valid example is in [`docs/profile.example.json`](./profile.example.json).
It contains every root field and one or more entries in every collection, using the
canonical plural keys.

Minimal valid profile (only required fields):

```json
{
  "id": "fac-2049abcd",
  "name": "Dr. Example Faculty",
  "facultyId": "UCEN-CSE-9999",
  "department": "Computer Science and Engineering",
  "designation": "Assistant Professor",
  "email": "example@ucen.ac.in",
  "contact": "+91 90000 00000",
  "specialization": "Machine Learning, Data Science",
  "photo": "",
  "banner": "",
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
```

---

## 9. Database schema (PostgreSQL)

The schema is normalised: one root table plus one child table per collection.
Adjust types to your DB engine if needed.

> **Implemented MySQL/MariaDB DDL:**
> [`backend/db/schema.sql`](../backend/db/schema.sql), applied with
> `php backend/bin/db-migrate.php`. It keeps the same table and column names but
> makes these deliberate adaptations for MariaDB and for byte-identical JSON
> round-tripping: child ids are `VARCHAR` (the app generates string ids),
> `created_at` / `updated_at` / `last_login_at` are `VARCHAR(40)` ISO-8601
> strings, `YYYY-MM` month fields are `VARCHAR(7)` and day fields are `DATE`;
> `NOT NULL` / `CHECK` constraints are relaxed because validation is
> authoritative in `FacultyService`. Single-dean uniqueness uses a generated
> `dean_unique` column, and `utf8mb4_unicode_ci` makes `faculty_id` / `email`
> uniqueness case-insensitive. The reference PostgreSQL schema below remains the
> normative field list.

```sql
CREATE EXTENSION IF NOT EXISTS citext;

-- ─── Root profile ───────────────────────────────────────────────────────────
CREATE TABLE faculty_profiles (
  id                        text PRIMARY KEY,
  name                      text NOT NULL,
  faculty_id                text NOT NULL UNIQUE,
  department                text NOT NULL,
  designation               text NOT NULL,
  email                     citext NOT NULL UNIQUE,
  contact                   text NOT NULL,
  specialization            text NOT NULL DEFAULT '',
  photo_url                 text,
  banner_url                text,
  research_guide_number     text,
  research_guide_university text,
  research_guide_date       date,
  research_guide_status     text CHECK (
    research_guide_status IS NULL OR research_guide_status IN
    ('Recognized', 'Provisional', 'Expired', 'Not Applicable')
  ),
  phd_completed             smallint NOT NULL DEFAULT 0 CHECK (phd_completed >= 0),
  mphil_completed           smallint NOT NULL DEFAULT 0 CHECK (mphil_completed >= 0),
  other_completed           smallint NOT NULL DEFAULT 0 CHECK (other_completed >= 0),
  phd_guiding               smallint NOT NULL DEFAULT 0 CHECK (phd_guiding >= 0),
  mphil_guiding             smallint NOT NULL DEFAULT 0 CHECK (mphil_guiding >= 0),
  other_guiding             smallint NOT NULL DEFAULT 0 CHECK (other_guiding >= 0),
  google_scholar_id         text,
  google_scholar_url        text,
  orcid_id                  text,
  orcid_url                 text,
  scopus_id                 text,
  scopus_url                text,
  is_dean                   boolean NOT NULL DEFAULT false,
  display_order             integer NOT NULL DEFAULT 0,
  created_at                timestamptz NOT NULL DEFAULT now(),
  updated_at                timestamptz NOT NULL DEFAULT now()
);

CREATE UNIQUE INDEX one_dean ON faculty_profiles (is_dean) WHERE is_dean;
CREATE INDEX faculty_directory_idx
  ON faculty_profiles (department, designation, name);

-- ─── Users / auth ───────────────────────────────────────────────────────────
CREATE TABLE users (
  id                 uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  email              citext NOT NULL UNIQUE,
  password_hash      text NOT NULL,
  role               text NOT NULL CHECK (role IN ('admin', 'faculty', 'viewer')),
  faculty_profile_id text UNIQUE REFERENCES faculty_profiles(id) ON DELETE SET NULL,
  is_active          boolean NOT NULL DEFAULT true,
  last_login_at      timestamptz,
  created_at         timestamptz NOT NULL DEFAULT now(),
  updated_at         timestamptz NOT NULL DEFAULT now()
);

-- ─── Child tables ───────────────────────────────────────────────────────────
CREATE TABLE faculty_qualifications (
  id            uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  profile_id    text NOT NULL REFERENCES faculty_profiles(id) ON DELETE CASCADE,
  sort_order    integer NOT NULL DEFAULT 0,
  degree        text NOT NULL,
  institution   text NOT NULL,
  university    text,
  year          smallint NOT NULL CHECK (year BETWEEN 1950 AND 2100),
  class_obtained text CHECK (
    class_obtained IS NULL OR class_obtained IN
    ('Distinction', '1st Class', '2nd Class', '3rd Class', 'Pass')
  ),
  specialization text
);

CREATE TABLE faculty_experiences (
  id          uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  profile_id  text NOT NULL REFERENCES faculty_profiles(id) ON DELETE CASCADE,
  sort_order  integer NOT NULL DEFAULT 0,
  institution text NOT NULL,
  designation text NOT NULL,
  from_month  date NOT NULL,
  to_month    date,
  total_experience text
);

CREATE TABLE faculty_publications (
  id          uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  profile_id  text NOT NULL REFERENCES faculty_profiles(id) ON DELETE CASCADE,
  sort_order  integer NOT NULL DEFAULT 0,
  title       text NOT NULL,
  type        text NOT NULL CHECK (type IN
    ('Journal Article', 'Conference Paper', 'Book', 'Book Chapter', 'Review Article', 'Other')),
  venue       text NOT NULL,
  authors     text,
  year        smallint NOT NULL CHECK (year BETWEEN 1950 AND 2100),
  volume      text,
  issue       text,
  pages       text,
  publisher   text,
  indexing    text,
  doi         text,
  url         text
);

CREATE TABLE faculty_projects (
  id                         uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  profile_id                 text NOT NULL REFERENCES faculty_profiles(id) ON DELETE CASCADE,
  sort_order                 integer NOT NULL DEFAULT 0,
  title                      text NOT NULL,
  agency                     text NOT NULL,
  category                   text CHECK (category IS NULL OR category IN
    ('Government Funded', 'University Funded', 'Industry Funded', 'Research Grant', 'Other')),
  principal_investigator     text,
  co_principal_investigator  text,
  faculty_role               text,
  duration                   text,
  start_month                date,
  end_month                  date,
  amount                     numeric(14,2) CHECK (amount IS NULL OR amount >= 0),
  status                     text NOT NULL CHECK (status IN ('Ongoing', 'Completed')),
  reference                  text,
  description                text,
  sanction_letter_url        text
);

CREATE TABLE faculty_conferences_attended (
  id           uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  profile_id   text NOT NULL REFERENCES faculty_profiles(id) ON DELETE CASCADE,
  sort_order   integer NOT NULL DEFAULT 0,
  name         text NOT NULL,
  type         text,
  category     text CHECK (category IS NULL OR category IN ('National', 'International')),
  organizer    text,
  location     text,
  start_date   date,
  end_date     date,
  participation text,
  paper_title  text,
  certificate_url text
);

CREATE TABLE faculty_conferences_conducted (
  id           uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  profile_id   text NOT NULL REFERENCES faculty_profiles(id) ON DELETE CASCADE,
  sort_order   integer NOT NULL DEFAULT 0,
  name         text NOT NULL,
  type         text,
  category     text CHECK (category IS NULL OR category IN ('National', 'International')),
  organizer    text,
  faculty_role text,
  location     text,
  start_date   date,
  end_date     date,
  participants integer CHECK (participants IS NULL OR participants >= 0),
  website      text
);

CREATE TABLE faculty_training_programs (
  id           uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  profile_id   text NOT NULL REFERENCES faculty_profiles(id) ON DELETE CASCADE,
  sort_order   integer NOT NULL DEFAULT 0,
  title        text NOT NULL,
  type         text,
  organizer    text,
  level        text CHECK (level IS NULL OR level IN
    ('National', 'International', 'State', 'Institutional')),
  start_date   date,
  end_date     date,
  faculty_role text,
  participants integer CHECK (participants IS NULL OR participants >= 0),
  certificate_url text
);

CREATE TABLE faculty_patents (
  id           uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  profile_id   text NOT NULL REFERENCES faculty_profiles(id) ON DELETE CASCADE,
  sort_order   integer NOT NULL DEFAULT 0,
  title        text NOT NULL,
  patent_number text NOT NULL,
  patent_type  text,
  inventors    text,
  authority    text,
  country      text,
  grant_date   date NOT NULL,
  status       text NOT NULL CHECK (status IN ('Granted', 'Awarded')),
  url          text,
  certificate_url text
);

CREATE TABLE faculty_research_areas (
  id             uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  profile_id     text NOT NULL REFERENCES faculty_profiles(id) ON DELETE CASCADE,
  sort_order     integer NOT NULL DEFAULT 0,
  area           text NOT NULL,
  subject        text,
  specialization text,
  keywords       text
);

CREATE TABLE faculty_achievements (
  id                 uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  profile_id         text NOT NULL REFERENCES faculty_profiles(id) ON DELETE CASCADE,
  sort_order         integer NOT NULL DEFAULT 0,
  title              text NOT NULL,
  category           text,
  organization       text NOT NULL,
  year               text,
  certificate_number text,
  certificate_url    text,
  description        text,
  certificate_file_url text
);

CREATE TABLE faculty_certifications (
  id                 uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  profile_id         text NOT NULL REFERENCES faculty_profiles(id) ON DELETE CASCADE,
  sort_order         integer NOT NULL DEFAULT 0,
  title              text NOT NULL,
  category           text,
  organization       text NOT NULL,
  year               text,
  certificate_number text,
  certificate_url    text,
  description        text,
  certificate_file_url text
);

CREATE TABLE faculty_responsibilities (
  id          uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  profile_id  text NOT NULL REFERENCES faculty_profiles(id) ON DELETE CASCADE,
  sort_order  integer NOT NULL DEFAULT 0,
  title       text NOT NULL,
  category    text,
  institution text,
  position    text,
  level       text,
  from_month  date,
  to_month    date,
  is_current  boolean NOT NULL DEFAULT false
);

-- ─── Optional: uploaded assets ──────────────────────────────────────────────
CREATE TABLE assets (
  id           uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  owner_profile_id text REFERENCES faculty_profiles(id) ON DELETE CASCADE,
  storage_key  text NOT NULL UNIQUE,
  url          text NOT NULL,
  mime_type    text NOT NULL,
  byte_size    bigint NOT NULL,
  original_name text,
  created_at   timestamptz NOT NULL DEFAULT now()
);
```

### Relationship summary

```text
users 1 ──── 0..1 faculty_profiles
faculty_profiles 1 ──── * faculty_qualifications
                     * faculty_experiences
                     * faculty_publications
                     * faculty_projects
                     * faculty_conferences_attended
                     * faculty_conferences_conducted
                     * faculty_training_programs
                     * faculty_patents
                     * faculty_research_areas
                     * faculty_achievements
                     * faculty_certifications
                     * faculty_responsibilities
                     * assets
```

---

## 10. Validation rules

### Server-side validation (authoritative)

| Field | Rule | Error code |
|---|---|---|
| `name` | required, 1–120 chars | `REQUIRED` / `TOO_LONG` |
| `facultyId` | required, unique, 1–40 chars | `DUPLICATE` |
| `department` | required, one of 8 | `INVALID_ENUM` |
| `designation` | required, one of 10 | `INVALID_ENUM` |
| `email` | required, valid email, unique, lower-case | `INVALID_EMAIL` / `DUPLICATE` |
| `contact` | required, 7–15 digits after stripping non-digits | `INVALID_PHONE` |
| `specialization` | required, non-empty | `REQUIRED` |
| `photo`, `banner` | MIME `image/jpeg\|png\|webp`, ≤5 MB | `INVALID_FILE` |
| `year` (qualification/publication) | integer 1950 … current year + 1 | `OUT_OF_RANGE` |
| `amount` | numeric ≥0 | `OUT_OF_RANGE` |
| `participants`, scholar counts | integer ≥0 | `OUT_OF_RANGE` |
| `from`, `to`, `start`, `end` | valid dates; `to >= from`, `end >= start` | `INVALID_RANGE` |
| `doi` | DOI or http(s) URL | `INVALID_URL` |
| `url`, `website`, `certificateUrl`, research URLs | `http`/`https` only | `INVALID_URL` |
| entry file fields | image or PDF, ≤3 MB | `INVALID_FILE` |
| `status` (project) | `Ongoing` or `Completed` | `INVALID_ENUM` |
| `status` (patent) | `Granted` or `Awarded` | `INVALID_ENUM` |
| `current` | boolean-ish | `INVALID_BOOLEAN` |
| `isDean` | at most one true | `CONFLICT` |

### Length limits (recommended)

Titles/names 300, institution/agency 200, venue 250, degree 180,
specialization 180, description 5000, certificate number 120, URLs 2048.

### Error response shape

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Please correct the highlighted fields.",
    "fields": {
      "email": "A faculty member with this email already exists.",
      "qualifications[0].year": "Year must be between 1950 and 2027."
    }
  }
}
```

---

## 11. File and asset contract

### Current behaviour (demo)

| Asset | Accepted MIME | Size limit | Client processing | Stored as |
|---|---|---|---|---|
| Profile photo | JPEG, PNG, WebP | 5 MB | Centre-cropped to 480×480 JPEG, quality 0.86 | base64 data URL in `photo` |
| Profile banner | JPEG, PNG, WebP | 5 MB | Cover-cropped to 1200×320 JPEG, quality 0.82 | base64 data URL in `banner` |
| Sanction letter | JPEG, PNG, WebP, GIF, PDF | 3 MB | None | base64 data URL in `sanctionLetter` |
| Certificates | JPEG, PNG, WebP, GIF, PDF | 3 MB | None | base64 data URL in `certificate` |
| External certificate link | URL | — | Validated `http(s)` | `certificateUrl` |

The demo server accepts JSON bodies up to **25 MB**, which is why base64 works but is
not scalable.

### Recommended production behaviour

1. `POST /api/v1/uploads` with `multipart/form-data` (`file`, optional `kind`).
2. Server validates MIME + size, strips EXIF, re-encodes images, stores in object
   storage (S3/R2/local), returns a stable URL.
3. Frontend saves only the returned URL in the profile/collection field.
4. Serve images through a CDN or signed URL; never expose raw bucket paths.
5. Keep `certificateUrl` (external link) separate from `certificate`
   (uploaded file URL).

Suggested response:

```json
{
  "data": {
    "id": "asset_uuid",
    "url": "https://cdn.example.edu/faculty/fac-ramanathan/photo.jpg",
    "mimeType": "image/jpeg",
    "size": 48123
  }
}
```

---

## 12. UI element → API → database mapping

| Screen element | API field(s) | DB table.column |
|---|---|---|
| Hero name | `profile.name` | `faculty_profiles.name` |
| Hero role note | session `role` | `users.role` |
| Strength % | computed completion | derived from all tables |
| Admin total / complete / incomplete / rate | `AdminOverview` | derived from `faculty_profiles` + children |
| Department bars | `AdminOverview.departments` | `faculty_profiles.department` |
| Dashboard profile strip | root fields | `faculty_profiles` |
| Dashboard tags | `specialization`, `department`, `facultyId` | `faculty_profiles` |
| Mini stats | child counts | child tables |
| Directory card name/designation/dept | root fields | `faculty_profiles` |
| Directory card tags | `specialization` | `faculty_profiles.specialization` |
| Directory card footer | `publications`, `projects` counts | child tables |
| Directory search | `name`, `department`, `designation`, `specialization`, `facultyId`, `email` | `faculty_profiles` |
| Dean spotlight | `name`, `designation`, `department`, `photo`, `id`, `isDean` | `faculty_profiles` |
| Form personal fields | root fields | `faculty_profiles` |
| Form qualification rows | `qualifications[]` | `faculty_qualifications` |
| Form experience rows | `experience[]` | `faculty_experiences` |
| Form publications | `publications[]` | `faculty_publications` |
| Form conferences attended | `conferencesAttended[]` | `faculty_conferences_attended` |
| Form conferences conducted | `conferencesConducted[]` | `faculty_conferences_conducted` |
| Form training programs | `trainingPrograms[]` | `faculty_training_programs` |
| Form patents | `patents[]` | `faculty_patents` |
| Form funded projects | `projects[]` | `faculty_projects` |
| Form research areas | `researchAreas[]` | `faculty_research_areas` |
| Form research guide + counts | root fields | `faculty_profiles` |
| Form achievements | `achievements[]` | `faculty_achievements` |
| Form certifications | `certifications[]` | `faculty_certifications` |
| Form responsibilities | `responsibilities[]` | `faculty_responsibilities` |
| Form research IDs | root fields | `faculty_profiles` |
| Profile hero banner | `banner` | `faculty_profiles.banner_url` |
| Profile hero avatar | `photo` | `faculty_profiles.photo_url` |
| Research scholar cards | `phdCompleted` … `otherGuiding` | `faculty_profiles` |
| All attachment chips | `sanctionLetter`, `certificate` | child `*_url` columns / `assets` |

---

## 13. Client normalisation, aliases and legacy fields

The frontend normalises every profile on load and save. Backend responses should follow
the canonical shape, but these rules make the client tolerant:

### Accepted root aliases

| Canonical | Also accepted |
|---|---|
| `qualifications` | `qualification` |
| `publications` | `publication` |
| `projects` | `project` |
| `achievements` | `achievement` |
| `certifications` | `certification` |
| `conferencesAttended` | `conferenceAttended` |
| `conferencesConducted` | `conferenceConducted` |
| `trainingPrograms` | `trainingProgram` |
| `patents` | `patent` |
| `responsibilities` | `responsibility` |
| `researchAreas` | `researchArea` |

The client also mirrors the singular entry keys and plural display keys, so both may
appear in `localStorage`. A backend only needs to return one form (prefer plural).

### Defaults and coercions

- Missing `id` → `fac-owner`.
- Missing `name` → fallback name or `Faculty Member`.
- Missing `department` → normalised to an empty string (unknown values are cleared).
- Dean `id` / `dean@ucen.ac.in` → designation defaults to `Dean & Professor`.
- Every scalar is coerced to a trimmed string.
- Empty child entries (all fields empty) are dropped.
- Duplicate roster ids get `-2`, `-3` suffixes.
- If the dean is missing from the roster, one is appended.
- Project `status` `Approved`/`Proposed` → `Ongoing`.
- Publication type inference described in section 7.4.

### Fields present in demo seed data but NOT displayed

| Field | Where | Status |
|---|---|---|
| `bio` | `SAMPLE_FACULTY` | Dropped by normaliser; not in form or view |
| `link` | legacy publication entries | Converted to `doi`/`url` |
| `description` | legacy experience entries | Dropped; use project/achievement descriptions instead |

Do not build backend columns for `bio`, `experience.description`, or
`publication.link` unless the UI is extended.

---

## 14. End-to-end flows

### 14.1 App load

```text
Browser
  │ GET /api/v1/bootstrap
  │ GET /api/v1/auth/me
  │ GET /api/v1/faculty            (roster; dean included or fetched separately)
  │ GET /api/v1/dean
  ▼
Normalise profiles → compute completion → render dashboard + directory
If no session → show role gate
```

With the **unchanged legacy frontend**:

```text
Browser
  │ GET /api/state
  ▼
{ "roster": [...] } → localStorage → seed fallback
```

### 14.2 Login

```text
/staff/login.php            /admin/login.php
  │ Staff ID + password ──────▶ POST /auth/login {expectedRole:"faculty"}
  │ Admin ID (or email) + password ─▶ POST /auth/login {expectedRole:"admin"}
  ▼
Server validates credentials + role, sets HttpOnly cookie
  ▼
200 { user, profile } → redirect to /staff/workspace.php or /admin/dashboard.php
```

Registration is whitelist-gated: a staff account needs an approved staff ID
(`validuser`) and an administrator account an approved admin ID (`validadmin`).

Failure cases the UI currently distinguishes:

- Empty ID/password → inline field error.
- Unknown ID, wrong password or wrong portal → one generic message
  ("Staff ID or password is incorrect." / "Admin ID or password is incorrect.")
  so the response never reveals which part failed.

The backend should return a generic `INVALID_CREDENTIALS` to avoid account enumeration;
the frontend can map it to a generic message.

### 14.3 Create / edit profile

```text
Admin clicks Add Faculty ──▶ blank form (POST /faculty)
Faculty clicks Create/Edit ─▶ own form  (PUT /faculty/:id)
  │ fill 14 form sections
  │ Preview ──▶ client-side render (no API)
  │ Save
  ▼
Validate (client + server)
  │ Upload files first ──▶ POST /uploads → URLs
  ▼
POST /faculty or PUT /faculty/:id  { full profile }
  ▼
201/200 FacultyProfile → update roster, close modal, show profile view + toast
```

### 14.4 Directory search

```text
Input / select change
  │ (current) filter roster in memory
  │ (scale)   GET /faculty?q=&department=&designation=
  ▼
Render cards, update #directory-count, show empty state if 0
```

### 14.5 Admin overview

```text
Admin opens dashboard
  │ GET /admin/overview
  ▼
Render total / complete / incomplete / rate + department bars
  ▼
Completion card shows institution-wide rate instead of personal rate
```

### 14.6 Delete / reset

The demo has only "Reset demo data", which clears local state and re-seeds. A real
backend needs an explicit admin delete/soft-delete; the frontend currently has no
delete button, so this is a UI addition if required.

---

## 15. Seed data

The frontend ships with 11 demo profiles:

| Id | Name | Role in demo |
|---|---|---|
| `fac-ramanathan` | Dr. K. Ramanathan | Primary faculty owner, complete profile |
| `fac-anitha` | Dr. M. Anitha | ECE |
| `fac-priya` | Dr. G. Priya | MBA |
| `fac-suresh` | Dr. R. Suresh | Mechanical |
| `fac-meenakshi` | Dr. S. Meenakshi | IT |
| `fac-karthik` | Dr. A. Karthik | Civil |
| `fac-lakshmi` | Dr. P. Lakshmi | Science & Humanities |
| `fac-rajesh` | Dr. V. Rajesh | EEE |
| `fac-nandhini` | Dr. S. Nandhini | CSE / cyber security |
| `fac-ananya` | Dr. Ananya Nair | IT, incomplete profile |
| `fac-vishal` | Mr. Vishal Menon | ECE, incomplete profile |
| `fac-dean` | Dr.T Sree Renga Raja | Dean spotlight (excluded from directory) |

The exact seed values are in `frontend/js/script.js` (`SAMPLE_OWNER`,
`SAMPLE_FACULTY`, `SAMPLE_DEAN`). A backend seed script can reuse
[`docs/profile.example.json`](./profile.example.json) as the complete-profile fixture.

---

## 16. Gaps, assumptions and recommendations

### Decisions to confirm with the product owner

1. **`specialization` shape** — currently a comma-separated string. Recommended:
   keep the string for frontend compatibility; optionally also expose
   `specializationTags: string[]`.
2. **`facultyId` vs `id`** — `id` is the internal key; `facultyId` is the employee ID.
   Do not conflate them.
3. **Dean representation** — current frontend hard-codes `fac-dean`. Recommended:
   add `isDean` and keep the id stable, or expose `GET /dean`.
4. **Approval/publish workflow** — the current UI has no draft/published state. If
   profiles require approval, add `status` (`draft`, `pending`, `published`) and a
   `publishedAt` field; the UI will need a badge/filter.
5. **Delete** — no delete UI exists. Add an admin delete action if required.
6. **Bio** — seed data contains `bio`, but it is not displayed. Either add it to the
   form + profile view, or drop it from the backend model.
7. **Photo privacy** — photos are public in the directory. Confirm consent/data policy.
8. **Soft delete vs hard delete** — recommend soft delete (`deleted_at`) for audit.
9. **Pagination** — the demo renders every profile. Add pagination before the roster
   exceeds a few hundred.
10. **Audit trail** — recommended for admin edits: who changed what and when.

### Performance recommendations

- Return the roster without child collections for the directory
  (`GET /faculty?view=summary`) and fetch full profiles on demand.
- Cache `/bootstrap` aggressively (versioned config).
- Index `department`, `designation`, `faculty_id`, `email`.
- Use full-text search (`pg_trgm` or `tsvector`) for the directory query.

---

## 17. Backend acceptance checklist

- [ ] `GET /api/state` returns `{ "roster": [...] }` for the unchanged frontend, **or**
      the frontend is migrated to `/api/v1`.
- [ ] Every profile returned has a unique `id` and the required root fields.
- [ ] All 12 collections are always arrays (never `null`/missing).
- [ ] `GET /bootstrap` supplies departments, designations, roles, and enum lists.
- [ ] Auth is server-side; roles are enforced on every write.
- [ ] Faculty can edit only their own profile; viewers cannot write.
- [ ] Admin overview numbers match the definitions in section 7.15.
- [ ] Dean profile is present, has `isDean = true`, and is excluded from directory
      results/statistics.
- [ ] Child entries support stable ids and preserve `sortOrder`.
- [ ] Uploads validate MIME/size and return stable URLs.
- [ ] Dates are ISO (`YYYY-MM-DD`) and months are `YYYY-MM`.
- [ ] Validation errors use the documented envelope and field paths.
- [ ] `/health` and structured server logs are available.
- [ ] Database migrations and seed fixture are committed.
