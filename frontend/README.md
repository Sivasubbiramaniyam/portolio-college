# UCEN Faculty Portal — Frontend

Vanilla HTML5 / CSS3 / JavaScript single-page application for the UCEN faculty
profile portal. No frameworks, no build step.

## Files

```text
frontend/
├── index.html        # All views, forms and overlays
├── css/style.css     # Design system, responsive layout, print styles
├── js/script.js      # State, rendering, validation, API access
├── server.js         # Optional Node static server for this demo
└── assets/           # University crest and default avatars
```

## Run

From the repository root:

```bash
npm start
```

Then open <http://localhost:3000/>. The Node server serves this directory and exposes
the legacy demo endpoint `/api/state`.

You can also open `frontend/index.html` directly in a browser. In that case network
persistence is disabled and the app uses `localStorage` only.

## Backend contract

Everything this frontend needs from a backend — every model, field, enum, API
endpoint, database table and validation rule — is documented in:

- [`../docs/BACKEND-DATA-CONTRACT.md`](../docs/BACKEND-DATA-CONTRACT.md)
- [`../docs/profile.example.json`](../docs/profile.example.json)

## Demo data

The app seeds demo profiles from `js/script.js` and stores edits in
`localStorage` under the key `ucen-faculty-portal-v2`. "Reset demo data" in the footer
restores the seed roster.

> Demo credentials are hard-coded for presentation only. Real authentication and
> authorisation must be enforced by the backend.
