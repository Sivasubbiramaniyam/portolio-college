# Database dump

This folder holds the complete, commented MySQL/MariaDB database dump for the
UCEN Faculty Portal.

| File | Purpose |
|---|---|
| `ucen_portal_dump.sql` | Everything needed to recreate `ucen_portal`: database creation, full schema (16 tables), the 12 canonical faculty profiles with all child rows, the staff/admin auth migration with whitelist seeds, and commented verification queries. |

## Restore

```bash
# CLI (XAMPP)
C:\xampp\mysql\bin\mysql.exe -u root -p < database/ucen_portal_dump.sql

# Or import the file through phpMyAdmin: Import → SQL.
```

The dump contains `CREATE DATABASE IF NOT EXISTS` and `USE`, so no manual
setup is required. `DROP TABLE IF EXISTS` statements are included as comments
near the top — uncomment them to rebuild from scratch. Re-importing on top of
an existing populated database will fail on duplicate keys; use the
application's idempotent seed command in that case.

## How the dump was produced

- Schema text is copied verbatim from `backend/db/schema.sql`.
- Seed `INSERT`s are generated from `backend/storage/seed/faculty.json` using
  `ProfileShape::normalize()` and the private field maps in
  `MysqlFacultyRepository`, so the rows are identical to what `db-seed.php`
  writes at runtime.
- Migration text is copied verbatim from
  `backend/db/migrations/2026-11-15-staff-admin-auth.sql`; it is idempotent.
- No user accounts or password hashes are included. Create them after a restore
  with `backend/bin/make-user.php`.

## Original application commands (for reference)

```bash
php backend/bin/db-migrate.php                 # create database + tables
php backend/bin/db-migrate.php --fresh         # drop tables first, then recreate
php backend/bin/db-seed.php                    # load seed/faculty.json (idempotent)
php backend/bin/db-seed.php --from=path.json   # load a different profile file
php backend/bin/make-user.php --role=admin --admin-id=admin --email=you@ucen.ac.in
php backend/bin/make-user.php --role=faculty --staff-id=UCEN-CSE-1042 --email=faculty@ucen.ac.in
```

Connection settings come from `DB_HOST`, `DB_PORT`, `DB_DATABASE`,
`DB_USERNAME`, `DB_PASSWORD` and `DB_CHARSET` (see `.env.example`).
