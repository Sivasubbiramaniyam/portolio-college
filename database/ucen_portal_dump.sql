-- =============================================================================
-- UCEN Faculty Portal — complete MySQL / MariaDB database dump
-- =============================================================================
-- Database : ucen_portal (override with DB_DATABASE in the environment)
-- Engine   : InnoDB, charset utf8mb4, collation utf8mb4_unicode_ci
-- Generated: 2026-11-17
--
-- This single file collects every database command used by the application:
--   1. database creation / table setup
--   2. full schema (DDL) — copied verbatim from backend/db/schema.sql
--   3. seed data (DML) — the 12 canonical faculty profiles and their child
--      collections, generated from backend/storage/seed/faculty.json using the
--      exact field mapping in MysqlFacultyRepository
--   4. the staff/admin authentication migration — copied verbatim from
--      backend/db/migrations/2026-11-15-staff-admin-auth.sql (whitelist seeds
--      and account backfill). It is idempotent and safe to re-run.
--   5. verification queries
--
-- HOW TO RESTORE
--   CLI : C:\xampp\mysql\bin\mysql.exe -u root -p < database/ucen_portal_dump.sql
--   Or paste/import this file in phpMyAdmin (Import → SQL).
--
-- The application's own commands (kept here for reference as comments):
--   php backend/bin/db-migrate.php                 # create DB + tables from schema.sql
--   php backend/bin/db-migrate.php --fresh         # drop tables first, then recreate
--   php backend/bin/db-seed.php                    # load seed/faculty.json (idempotent)
--   php backend/bin/db-seed.php --from=path.json   # load a different profile file
--   php backend/bin/make-user.php --role=admin --admin-id=admin --email=you@ucen.ac.in
--   php backend/bin/make-user.php --role=faculty --staff-id=UCEN-CSE-1042 --email=faculty@ucen.ac.in
--
-- No passwords or password hashes are shipped: user accounts are created with
-- make-user.php at deployment time. The users table is therefore empty here.
-- =============================================================================

SET NAMES utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. Database
-- -----------------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `ucen_portal`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ucen_portal`;

-- To rebuild from scratch, uncomment the following DROP statements before
-- importing (FOREIGN_KEY_CHECKS is disabled at the top of this file):
-- DROP TABLE IF EXISTS `assets`;
-- DROP TABLE IF EXISTS `faculty_responsibilities`;
-- DROP TABLE IF EXISTS `faculty_certifications`;
-- DROP TABLE IF EXISTS `faculty_achievements`;
-- DROP TABLE IF EXISTS `faculty_research_areas`;
-- DROP TABLE IF EXISTS `faculty_patents`;
-- DROP TABLE IF EXISTS `faculty_training_programs`;
-- DROP TABLE IF EXISTS `faculty_conferences_conducted`;
-- DROP TABLE IF EXISTS `faculty_conferences_attended`;
-- DROP TABLE IF EXISTS `faculty_projects`;
-- DROP TABLE IF EXISTS `faculty_publications`;
-- DROP TABLE IF EXISTS `faculty_experiences`;
-- DROP TABLE IF EXISTS `faculty_qualifications`;
-- DROP TABLE IF EXISTS `users`;
-- DROP TABLE IF EXISTS `validadmin`;
-- DROP TABLE IF EXISTS `validuser`;
-- DROP TABLE IF EXISTS `faculty_profiles`;

-- -----------------------------------------------------------------------------
-- 2. Schema (DDL) — source: backend/db/schema.sql
-- -----------------------------------------------------------------------------
-- UCEN Faculty Portal — MySQL / MariaDB schema
--
-- One root table (faculty_profiles) plus one child table per collection, and
-- users + assets. Column names mirror docs/BACKEND-DATA-CONTRACT.md.
--
-- Notes on deliberate deviations from the reference (PostgreSQL) schema:
--   * Child ids are VARCHAR, not uuid, because the application generates stable
--     string ids (fac-dean-qual-1, asset_<hex>, ...) and the JSON API must keep
--     returning exactly those values.
--   * created_at / updated_at / last_login_at are VARCHAR(40) because the
--     application stores ISO-8601 strings (gmdate('c')) and must round-trip them
--     byte-for-byte through the API.
--   * Month-precision fields (YYYY-MM) are VARCHAR(7); day-precision fields are
--     DATE. This preserves the exact values the validator accepts.
--   * NOT NULL / CHECK constraints from the reference schema are intentionally
--     relaxed: validation is authoritative in the service layer
--     (FacultyService), exactly as it is for the JSON provider. The database
--     enforces structure, referential integrity and uniqueness only.
--
-- Charset utf8mb4 + utf8mb4_unicode_ci makes faculty_id / email uniqueness
-- case-insensitive, matching the JSON provider's strtolower() comparisons.

SET NAMES utf8mb4;

-- ─── Root profile ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS faculty_profiles (
  id                        VARCHAR(80)  NOT NULL,
  name                      VARCHAR(255) NOT NULL DEFAULT '',
  faculty_id                VARCHAR(64)  NULL,
  department                VARCHAR(160) NOT NULL DEFAULT '',
  designation               VARCHAR(160) NOT NULL DEFAULT '',
  email                     VARCHAR(190) NULL,
  contact                   VARCHAR(40)  NOT NULL DEFAULT '',
  specialization            VARCHAR(500) NOT NULL DEFAULT '',
  photo_url                 MEDIUMTEXT   NULL,
  banner_url                MEDIUMTEXT   NULL,
  research_guide_number     VARCHAR(160) NULL,
  research_guide_university VARCHAR(200) NULL,
  research_guide_date       DATE         NULL,
  research_guide_status     VARCHAR(40)  NULL,
  phd_completed             INT          NOT NULL DEFAULT 0,
  mphil_completed           INT          NOT NULL DEFAULT 0,
  other_completed           INT          NOT NULL DEFAULT 0,
  phd_guiding               INT          NOT NULL DEFAULT 0,
  mphil_guiding             INT          NOT NULL DEFAULT 0,
  other_guiding             INT          NOT NULL DEFAULT 0,
  google_scholar_id         VARCHAR(190) NULL,
  google_scholar_url        TEXT         NULL,
  orcid_id                  VARCHAR(190) NULL,
  orcid_url                 TEXT         NULL,
  scopus_id                 VARCHAR(190) NULL,
  scopus_url                TEXT         NULL,
  is_dean                   TINYINT(1)   NOT NULL DEFAULT 0,
  display_order             INT          NOT NULL DEFAULT 0,
  created_at                VARCHAR(40)  NULL,
  updated_at                VARCHAR(40)  NULL,
  -- Generated column: NULL for every non-dean, 1 for the dean. A UNIQUE index on
  -- it allows many NULLs but at most one dean (MariaDB 10.2+/MySQL 5.7+).
  dean_unique               TINYINT(1)   AS (IF(is_dean = 1, 1, NULL)) STORED,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_faculty_id (faculty_id),
  UNIQUE KEY uniq_faculty_email (email),
  UNIQUE KEY uniq_dean (dean_unique),
  KEY faculty_directory_idx (department, designation, name),
  KEY faculty_order_idx (display_order, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Users / auth ───────────────────────────────────────────────────────────
--
-- Mirrors the production auth pattern (see the college-website repo):
--   * staff sign in with staff_id (users.staff_id),
--   * administrators sign in with admin_id (users.admin_id),
--   * self-registration is gated by the validuser / validadmin whitelists.
-- email is kept as a contact address and an administrator fallback login.
CREATE TABLE IF NOT EXISTS users (
  id                 VARCHAR(64)  NOT NULL,
  email              VARCHAR(190) NOT NULL,
  name               VARCHAR(190) NULL,
  staff_id           VARCHAR(190) NULL,
  admin_id           VARCHAR(190) NULL,
  department         VARCHAR(160) NOT NULL DEFAULT '',
  password_hash      VARCHAR(255) NOT NULL,
  role               VARCHAR(20)  NOT NULL,
  faculty_profile_id VARCHAR(80)  NULL,
  is_active          TINYINT(1)   NOT NULL DEFAULT 1,
  last_login_at      VARCHAR(40)  NULL,
  created_at         VARCHAR(40)  NULL,
  updated_at         VARCHAR(40)  NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_users_email (email),
  UNIQUE KEY uniq_users_staff_id (staff_id),
  UNIQUE KEY uniq_users_admin_id (admin_id),
  UNIQUE KEY uniq_users_faculty (faculty_profile_id),
  CONSTRAINT fk_users_profile FOREIGN KEY (faculty_profile_id)
    REFERENCES faculty_profiles (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pre-approved staff IDs. A staff account can only be created for an ID that
-- exists here; the stored name is authoritative for the account.
CREATE TABLE IF NOT EXISTS validuser (
  staff_id   VARCHAR(190) NOT NULL,
  name       VARCHAR(255) NULL,
  created_at VARCHAR(40)  NULL,
  PRIMARY KEY (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pre-approved administrator IDs, same idea as validuser.
CREATE TABLE IF NOT EXISTS validadmin (
  admin_id   VARCHAR(190) NOT NULL,
  name       VARCHAR(255) NULL,
  created_at VARCHAR(40)  NULL,
  PRIMARY KEY (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Child collections ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS faculty_qualifications (
  id             VARCHAR(120) NOT NULL,
  profile_id     VARCHAR(80)  NOT NULL,
  sort_order     INT          NOT NULL DEFAULT 0,
  degree         VARCHAR(255) NULL,
  institution    VARCHAR(255) NULL,
  university     VARCHAR(255) NULL,
  year           INT          NOT NULL DEFAULT 0,
  class_obtained VARCHAR(40)  NULL,
  specialization VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY profile_sort_idx (profile_id, sort_order),
  CONSTRAINT fk_qual_profile FOREIGN KEY (profile_id)
    REFERENCES faculty_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faculty_experiences (
  id               VARCHAR(120) NOT NULL,
  profile_id       VARCHAR(80)  NOT NULL,
  sort_order       INT          NOT NULL DEFAULT 0,
  institution      VARCHAR(255) NULL,
  designation      VARCHAR(160) NULL,
  from_month       VARCHAR(7)   NULL,
  to_month         VARCHAR(7)   NULL,
  total_experience VARCHAR(160) NULL,
  PRIMARY KEY (id),
  KEY profile_sort_idx (profile_id, sort_order),
  CONSTRAINT fk_exp_profile FOREIGN KEY (profile_id)
    REFERENCES faculty_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faculty_publications (
  id         VARCHAR(120) NOT NULL,
  profile_id VARCHAR(80)  NOT NULL,
  sort_order INT          NOT NULL DEFAULT 0,
  title      VARCHAR(400) NULL,
  type       VARCHAR(60)  NULL,
  venue      VARCHAR(400) NULL,
  authors    VARCHAR(600) NULL,
  year       INT          NOT NULL DEFAULT 0,
  volume     VARCHAR(60)  NULL,
  issue      VARCHAR(60)  NULL,
  pages      VARCHAR(80)  NULL,
  publisher  VARCHAR(200) NULL,
  indexing   VARCHAR(400) NULL,
  doi        TEXT         NULL,
  url        TEXT         NULL,
  PRIMARY KEY (id),
  KEY profile_sort_idx (profile_id, sort_order),
  CONSTRAINT fk_pub_profile FOREIGN KEY (profile_id)
    REFERENCES faculty_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faculty_projects (
  id                        VARCHAR(120) NOT NULL,
  profile_id                VARCHAR(80)  NOT NULL,
  sort_order                INT          NOT NULL DEFAULT 0,
  title                     VARCHAR(400) NULL,
  agency                    VARCHAR(300) NULL,
  category                  VARCHAR(60)  NULL,
  principal_investigator    VARCHAR(255) NULL,
  co_principal_investigator VARCHAR(255) NULL,
  faculty_role              VARCHAR(200) NULL,
  duration                  VARCHAR(160) NULL,
  start_month               VARCHAR(7)   NULL,
  end_month                 VARCHAR(7)   NULL,
  amount                    VARCHAR(40)  NULL,
  status                    VARCHAR(40)  NULL,
  reference                 VARCHAR(160) NULL,
  description               TEXT         NULL,
  sanction_letter_url       MEDIUMTEXT   NULL,
  PRIMARY KEY (id),
  KEY profile_sort_idx (profile_id, sort_order),
  CONSTRAINT fk_prj_profile FOREIGN KEY (profile_id)
    REFERENCES faculty_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faculty_conferences_attended (
  id              VARCHAR(120) NOT NULL,
  profile_id      VARCHAR(80)  NOT NULL,
  sort_order      INT          NOT NULL DEFAULT 0,
  name            VARCHAR(400) NULL,
  type            VARCHAR(80)  NULL,
  category        VARCHAR(40)  NULL,
  organizer       VARCHAR(300) NULL,
  location        VARCHAR(200) NULL,
  start_date      DATE         NULL,
  end_date        DATE         NULL,
  participation   VARCHAR(80)  NULL,
  paper_title     VARCHAR(400) NULL,
  certificate_url MEDIUMTEXT   NULL,
  PRIMARY KEY (id),
  KEY profile_sort_idx (profile_id, sort_order),
  CONSTRAINT fk_att_profile FOREIGN KEY (profile_id)
    REFERENCES faculty_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faculty_conferences_conducted (
  id           VARCHAR(120) NOT NULL,
  profile_id   VARCHAR(80)  NOT NULL,
  sort_order   INT          NOT NULL DEFAULT 0,
  name         VARCHAR(400) NULL,
  type         VARCHAR(80)  NULL,
  category     VARCHAR(40)  NULL,
  organizer    VARCHAR(300) NULL,
  faculty_role VARCHAR(200) NULL,
  location     VARCHAR(200) NULL,
  start_date   DATE         NULL,
  end_date     DATE         NULL,
  participants INT          NOT NULL DEFAULT 0,
  website      TEXT         NULL,
  PRIMARY KEY (id),
  KEY profile_sort_idx (profile_id, sort_order),
  CONSTRAINT fk_cond_profile FOREIGN KEY (profile_id)
    REFERENCES faculty_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faculty_training_programs (
  id              VARCHAR(120) NOT NULL,
  profile_id      VARCHAR(80)  NOT NULL,
  sort_order      INT          NOT NULL DEFAULT 0,
  title           VARCHAR(400) NULL,
  type            VARCHAR(80)  NULL,
  organizer       VARCHAR(300) NULL,
  level           VARCHAR(40)  NULL,
  start_date      DATE         NULL,
  end_date        DATE         NULL,
  faculty_role    VARCHAR(200) NULL,
  participants    INT          NOT NULL DEFAULT 0,
  certificate_url MEDIUMTEXT   NULL,
  PRIMARY KEY (id),
  KEY profile_sort_idx (profile_id, sort_order),
  CONSTRAINT fk_trn_profile FOREIGN KEY (profile_id)
    REFERENCES faculty_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faculty_patents (
  id              VARCHAR(120) NOT NULL,
  profile_id      VARCHAR(80)  NOT NULL,
  sort_order      INT          NOT NULL DEFAULT 0,
  title           VARCHAR(400) NULL,
  patent_number   VARCHAR(120) NULL,
  patent_type     VARCHAR(160) NULL,
  inventors       VARCHAR(600) NULL,
  authority       VARCHAR(300) NULL,
  country         VARCHAR(100) NULL,
  grant_date      DATE         NULL,
  status          VARCHAR(40)  NULL,
  url             TEXT         NULL,
  certificate_url MEDIUMTEXT   NULL,
  PRIMARY KEY (id),
  KEY profile_sort_idx (profile_id, sort_order),
  CONSTRAINT fk_pat_profile FOREIGN KEY (profile_id)
    REFERENCES faculty_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faculty_research_areas (
  id             VARCHAR(120) NOT NULL,
  profile_id     VARCHAR(80)  NOT NULL,
  sort_order     INT          NOT NULL DEFAULT 0,
  area           VARCHAR(255) NULL,
  subject        VARCHAR(255) NULL,
  specialization VARCHAR(300) NULL,
  keywords       VARCHAR(600) NULL,
  PRIMARY KEY (id),
  KEY profile_sort_idx (profile_id, sort_order),
  CONSTRAINT fk_area_profile FOREIGN KEY (profile_id)
    REFERENCES faculty_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faculty_achievements (
  id                   VARCHAR(120) NOT NULL,
  profile_id           VARCHAR(80)  NOT NULL,
  sort_order           INT          NOT NULL DEFAULT 0,
  title                VARCHAR(400) NULL,
  category             VARCHAR(80)  NULL,
  organization         VARCHAR(300) NULL,
  year                 VARCHAR(80)  NULL,
  certificate_number   VARCHAR(160) NULL,
  certificate_url      TEXT         NULL,
  description          TEXT         NULL,
  certificate_file_url MEDIUMTEXT   NULL,
  PRIMARY KEY (id),
  KEY profile_sort_idx (profile_id, sort_order),
  CONSTRAINT fk_ach_profile FOREIGN KEY (profile_id)
    REFERENCES faculty_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faculty_certifications (
  id                   VARCHAR(120) NOT NULL,
  profile_id           VARCHAR(80)  NOT NULL,
  sort_order           INT          NOT NULL DEFAULT 0,
  title                VARCHAR(400) NULL,
  category             VARCHAR(80)  NULL,
  organization         VARCHAR(300) NULL,
  year                 VARCHAR(80)  NULL,
  certificate_number   VARCHAR(160) NULL,
  certificate_url      TEXT         NULL,
  description          TEXT         NULL,
  certificate_file_url MEDIUMTEXT   NULL,
  PRIMARY KEY (id),
  KEY profile_sort_idx (profile_id, sort_order),
  CONSTRAINT fk_cert_profile FOREIGN KEY (profile_id)
    REFERENCES faculty_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faculty_responsibilities (
  id          VARCHAR(120) NOT NULL,
  profile_id  VARCHAR(80)  NOT NULL,
  sort_order  INT          NOT NULL DEFAULT 0,
  title       VARCHAR(400) NULL,
  category    VARCHAR(80)  NULL,
  institution VARCHAR(300) NULL,
  position    VARCHAR(200) NULL,
  level       VARCHAR(80)  NULL,
  from_month  VARCHAR(7)   NULL,
  to_month    VARCHAR(7)   NULL,
  is_current  TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY profile_sort_idx (profile_id, sort_order),
  CONSTRAINT fk_resp_profile FOREIGN KEY (profile_id)
    REFERENCES faculty_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Uploaded assets ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS assets (
  id               VARCHAR(64)  NOT NULL,
  owner_profile_id VARCHAR(80)  NULL,
  storage_key      VARCHAR(200) NOT NULL,
  url              TEXT         NOT NULL,
  mime_type        VARCHAR(120) NOT NULL,
  byte_size        BIGINT       NOT NULL DEFAULT 0,
  original_name    VARCHAR(255) NULL,
  kind             VARCHAR(40)  NULL,
  uploaded_by      VARCHAR(64)  NULL,
  created_at       VARCHAR(40)  NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_assets_storage (storage_key),
  KEY assets_owner_idx (owner_profile_id),
  CONSTRAINT fk_assets_profile FOREIGN KEY (owner_profile_id)
    REFERENCES faculty_profiles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- 3. Seed data (DML) — source: backend/storage/seed/faculty.json
--    12 profiles (11 directory members + the dean). INSERTs are generated from
--    the canonical ProfileShape and the same column map the MySQL repository
--    uses at runtime, so a restore is byte-identical to running db-seed.php.
--    Re-running the dump on an existing database will fail on duplicate keys;
--    use the application's idempotent db-seed.php command in that case.
-- -----------------------------------------------------------------------------

-- Profile: fac-ramanathan (Dr. K. Ramanathan)
INSERT INTO `faculty_profiles` (`id`, `name`, `faculty_id`, `department`, `designation`, `email`, `contact`, `specialization`, `photo_url`, `banner_url`, `research_guide_number`, `research_guide_university`, `research_guide_date`, `research_guide_status`, `phd_completed`, `mphil_completed`, `other_completed`, `phd_guiding`, `mphil_guiding`, `other_guiding`, `google_scholar_id`, `google_scholar_url`, `orcid_id`, `orcid_url`, `scopus_id`, `scopus_url`, `is_dean`, `display_order`, `created_at`, `updated_at`) VALUES ('fac-ramanathan', 'Dr. K. Ramanathan', 'UCEN-CSE-1042', 'Computer Science and Engineering', 'Professor & Head', 'radhakrishnan1974@ucen.ac.in', '+91 94432 18765', 'Artificial Intelligence, Cloud Computing, Data Mining', '', '', 'AU/RC/CS/2012/034', 'Anna University, Chennai', '2012-05-18', 'Recognized', 6, 4, 2, 5, 1, 0, 'AbCdEf123456', 'https://scholar.google.com/citations?user=AbCdEf123456', '0000-0002-1825-0097', 'https://orcid.org/0000-0002-1825-0097', '57210493821', 'https://www.scopus.com/authid/detail.uri?authorId=57210493821', 0, 0, '2024-01-15T09:30:00.000Z', '2026-11-13T18:40:08.160Z');
-- faculty_qualifications — 3 row(s) for fac-ramanathan
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-ramanathan-qual-1', 'fac-ramanathan', 0, 'Ph.D. in Computer Science and Engineering', 'National Institute of Technology, Tiruchirappalli', 'Anna University, Chennai', 2009, 'Distinction', 'Artificial Intelligence');
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-ramanathan-qual-2', 'fac-ramanathan', 1, 'M.E. in Computer Science and Engineering', 'College of Engineering, Guindy', 'Anna University, Chennai', 2003, '1st Class', 'Software Engineering');
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-ramanathan-qual-3', 'fac-ramanathan', 2, 'B.E. in Computer Science and Engineering', 'Thiagarajar College of Engineering, Madurai', 'Madurai Kamaraj University', 2000, '1st Class', 'Computer Science');
-- faculty_experiences — 3 row(s) for fac-ramanathan
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-ramanathan-exp-1', 'fac-ramanathan', 0, 'University College of Engineering Nagercoil', 'Professor & Head', '2018-06', '', '');
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-ramanathan-exp-2', 'fac-ramanathan', 1, 'University College of Engineering Nagercoil', 'Professor', '2013-07', '2018-05', '');
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-ramanathan-exp-3', 'fac-ramanathan', 2, 'Sree Sastha Institute of Engineering and Technology, Chennai', 'Associate Professor', '2007-06', '2013-06', '');
-- faculty_publications — 4 row(s) for fac-ramanathan
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-ramanathan-pub-1', 'fac-ramanathan', 0, 'Explainable Deep Learning for Crop Disease Identification in Resource-Constrained Environments', 'Journal Article', 'IEEE Transactions on Emerging Topics in Computing', 'K. Ramanathan, M. Anitha, R. Suresh', 2024, '12', '3', '101-114', 'IEEE', 'Scopus, Web of Science', 'https://doi.org/10.1109/TETC.2024.10421873', 'https://doi.org/10.1109/TETC.2024.10421873');
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-ramanathan-pub-2', 'fac-ramanathan', 1, 'A Privacy-Preserving Architecture for Academic Credential Exchange', 'Journal Article', 'Journal of Information Security and Applications', 'K. Ramanathan, S. Meenakshi', 2023, '76', '2', '103562', 'Elsevier', 'Scopus, Web of Science', 'https://doi.org/10.1016/j.jisa.2023.103562', 'https://doi.org/10.1016/j.jisa.2023.103562');
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-ramanathan-pub-3', 'fac-ramanathan', 2, 'Adaptive Learning Paths for Regional Language Classrooms', 'Conference Paper', 'International Conference on Advanced Computing (ICoAC)', 'M. Anitha, K. Ramanathan', 2022, '', '', '1-6', 'IEEE', 'Scopus', '', '');
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-ramanathan-pub-4', 'fac-ramanathan', 3, 'Energy-Aware Scheduling for Federated Cloud Workloads', 'Journal Article', 'Cluster Computing', 'K. Ramanathan, R. Suresh', 2021, '24', '4', '3125-3140', 'Springer', 'Scopus, Web of Science', 'https://doi.org/10.1007/s10586-021-03245-8', 'https://doi.org/10.1007/s10586-021-03245-8');
-- faculty_projects — 3 row(s) for fac-ramanathan
INSERT INTO `faculty_projects` (`id`, `profile_id`, `sort_order`, `title`, `agency`, `category`, `principal_investigator`, `co_principal_investigator`, `faculty_role`, `duration`, `start_month`, `end_month`, `amount`, `status`, `reference`, `description`, `sanction_letter_url`) VALUES ('fac-ramanathan-prj-1', 'fac-ramanathan', 0, 'AI-Assisted Early Detection of Crop Disease for Smallholder Farmers', 'Science and Engineering Research Board (DST)', 'Government Funded', 'Dr. K. Ramanathan', 'Dr. M. Anitha', 'Principal Investigator', '2022 – 2025', '2022-04', '2025-03', '4850000', 'Ongoing', 'DST/SERB/2022/1042', 'A field-deployable computer vision system that helps farmers identify crop disease early using low-cost mobile devices and regional-language alerts.', '');
INSERT INTO `faculty_projects` (`id`, `profile_id`, `sort_order`, `title`, `agency`, `category`, `principal_investigator`, `co_principal_investigator`, `faculty_role`, `duration`, `start_month`, `end_month`, `amount`, `status`, `reference`, `description`, `sanction_letter_url`) VALUES ('fac-ramanathan-prj-2', 'fac-ramanathan', 1, 'Secure Cloud Framework for Academic Record Management', 'AICTE – Research Promotion Scheme', 'Government Funded', 'Dr. S. Meenakshi', 'Dr. K. Ramanathan', 'Co-Investigator', '2019 – 2022', '2019-07', '2022-06', '1850000', 'Completed', 'AICTE/RPS/2019/118', 'Designed an encrypted, audit-ready cloud architecture for inter-collegiate academic record exchange.', '');
INSERT INTO `faculty_projects` (`id`, `profile_id`, `sort_order`, `title`, `agency`, `category`, `principal_investigator`, `co_principal_investigator`, `faculty_role`, `duration`, `start_month`, `end_month`, `amount`, `status`, `reference`, `description`, `sanction_letter_url`) VALUES ('fac-ramanathan-prj-3', 'fac-ramanathan', 2, 'Intelligent Tutoring System for Regional Language Learners', 'Tamil Nadu State Council for Science and Technology', 'Research Grant', 'Dr. K. Ramanathan', '', 'Principal Investigator', '2024 – 2026', '2024-01', '2026-12', '950000', 'Ongoing', 'TNSCST/STP/2024/07', 'Adaptive learning platform that personalises practice material for first-generation learners in Tamil-medium schools.', '');
-- faculty_conferences_attended — 3 row(s) for fac-ramanathan
INSERT INTO `faculty_conferences_attended` (`id`, `profile_id`, `sort_order`, `name`, `type`, `category`, `organizer`, `location`, `start_date`, `end_date`, `participation`, `paper_title`, `certificate_url`) VALUES ('fac-ramanathan-att-1', 'fac-ramanathan', 0, 'IEEE International Conference on Advanced Computing', 'Technical Conference', 'International', 'IEEE Madras Section', 'Chennai, India', '2024-12-12', '2024-12-14', 'Paper Presenter', 'Explainable Deep Learning for Crop Disease Identification', '');
INSERT INTO `faculty_conferences_attended` (`id`, `profile_id`, `sort_order`, `name`, `type`, `category`, `organizer`, `location`, `start_date`, `end_date`, `participation`, `paper_title`, `certificate_url`) VALUES ('fac-ramanathan-att-2', 'fac-ramanathan', 1, 'National Symposium on Responsible Artificial Intelligence', 'Symposium', 'National', 'Anna University, Chennai', 'Chennai, India', '2023-09-08', '2023-09-09', 'Invited Speaker', '', '');
INSERT INTO `faculty_conferences_attended` (`id`, `profile_id`, `sort_order`, `name`, `type`, `category`, `organizer`, `location`, `start_date`, `end_date`, `participation`, `paper_title`, `certificate_url`) VALUES ('fac-ramanathan-att-3', 'fac-ramanathan', 2, 'Faculty Development Programme on Cloud-Native Systems', 'Workshop', 'National', 'NIT Tiruchirappalli', 'Tiruchirappalli, India', '2023-02-06', '2023-02-10', 'Participant', '', '');
-- faculty_conferences_conducted — 2 row(s) for fac-ramanathan
INSERT INTO `faculty_conferences_conducted` (`id`, `profile_id`, `sort_order`, `name`, `type`, `category`, `organizer`, `faculty_role`, `location`, `start_date`, `end_date`, `participants`, `website`) VALUES ('fac-ramanathan-cond-1', 'fac-ramanathan', 0, 'National Workshop on Applied Artificial Intelligence', 'Workshop', 'National', 'University College of Engineering Nagercoil', 'Convener', 'Nagercoil, Tamil Nadu', '2024-03-14', '2024-03-16', 180, '');
INSERT INTO `faculty_conferences_conducted` (`id`, `profile_id`, `sort_order`, `name`, `type`, `category`, `organizer`, `faculty_role`, `location`, `start_date`, `end_date`, `participants`, `website`) VALUES ('fac-ramanathan-cond-2', 'fac-ramanathan', 1, 'International Conference on Data-Driven Engineering', 'Research Conference', 'International', 'University College of Engineering Nagercoil', 'Organizing Secretary', 'Nagercoil, Tamil Nadu', '2022-11-10', '2022-11-12', 260, '');
-- faculty_training_programs — 2 row(s) for fac-ramanathan
INSERT INTO `faculty_training_programs` (`id`, `profile_id`, `sort_order`, `title`, `type`, `organizer`, `level`, `start_date`, `end_date`, `faculty_role`, `participants`, `certificate_url`) VALUES ('fac-ramanathan-trn-1', 'fac-ramanathan', 0, 'Faculty Development Programme on Generative AI', 'FDP', 'University College of Engineering Nagercoil', 'National', '2025-01-06', '2025-01-10', 'Coordinator', 60, '');
INSERT INTO `faculty_training_programs` (`id`, `profile_id`, `sort_order`, `title`, `type`, `organizer`, `level`, `start_date`, `end_date`, `faculty_role`, `participants`, `certificate_url`) VALUES ('fac-ramanathan-trn-2', 'fac-ramanathan', 1, 'Hands-on Training on Cloud-Native Development', 'Training', 'ICT Academy', 'State', '2023-07-17', '2023-07-21', 'Resource Person', 85, '');
-- faculty_patents — 2 row(s) for fac-ramanathan
INSERT INTO `faculty_patents` (`id`, `profile_id`, `sort_order`, `title`, `patent_number`, `patent_type`, `inventors`, `authority`, `country`, `grant_date`, `status`, `url`, `certificate_url`) VALUES ('fac-ramanathan-pat-1', 'fac-ramanathan', 0, 'Context-Aware Irrigation Advisory System', 'IN 412356', 'Utility Patent', 'K. Ramanathan, M. Anitha', 'Intellectual Property India', 'India', '2022-08-19', 'Granted', '', '');
INSERT INTO `faculty_patents` (`id`, `profile_id`, `sort_order`, `title`, `patent_number`, `patent_type`, `inventors`, `authority`, `country`, `grant_date`, `status`, `url`, `certificate_url`) VALUES ('fac-ramanathan-pat-2', 'fac-ramanathan', 1, 'Privacy-Preserving Academic Credential Verification Method', 'IN 431902', 'Utility Patent', 'K. Ramanathan, S. Meenakshi', 'Intellectual Property India', 'India', '2023-11-24', 'Awarded', '', '');
-- faculty_research_areas — 3 row(s) for fac-ramanathan
INSERT INTO `faculty_research_areas` (`id`, `profile_id`, `sort_order`, `area`, `subject`, `specialization`, `keywords`) VALUES ('fac-ramanathan-area-1', 'fac-ramanathan', 0, 'Artificial Intelligence', 'Computer Science and Engineering', 'Explainable AI, Computer Vision', 'deep learning, precision agriculture, model interpretability');
INSERT INTO `faculty_research_areas` (`id`, `profile_id`, `sort_order`, `area`, `subject`, `specialization`, `keywords`) VALUES ('fac-ramanathan-area-2', 'fac-ramanathan', 1, 'Cloud Computing', 'Computer Science and Engineering', 'Secure cloud architecture, federated systems', 'privacy, distributed scheduling, cloud security');
INSERT INTO `faculty_research_areas` (`id`, `profile_id`, `sort_order`, `area`, `subject`, `specialization`, `keywords`) VALUES ('fac-ramanathan-area-3', 'fac-ramanathan', 2, 'Data Mining', 'Computer Science and Engineering', 'Educational data mining, regional language analytics', 'learning analytics, Tamil NLP, inclusive education');
-- faculty_achievements — 3 row(s) for fac-ramanathan
INSERT INTO `faculty_achievements` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-ramanathan-ach-1', 'fac-ramanathan', 0, 'Best Faculty Researcher Award', 'Research Achievement', 'Anna University, Chennai', '2023', 'AU/AWD/2023/17', '', 'Recognised for sustained research output and externally funded projects in applied artificial intelligence.', '');
INSERT INTO `faculty_achievements` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-ramanathan-ach-2', 'fac-ramanathan', 1, 'Outstanding Contribution to Teaching', 'Teaching Achievement', 'University College of Engineering Nagercoil', '2021', 'UCEN/AWD/2021/09', '', 'Awarded for curriculum innovation and mentorship of student research teams.', '');
INSERT INTO `faculty_achievements` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-ramanathan-ach-3', 'fac-ramanathan', 2, 'Patent Granted – Context-Aware Irrigation Advisory System', 'Award', 'Intellectual Property India', '2022', 'IN 412356', '', 'A smart irrigation method that combines soil sensor data with local weather forecasts to reduce water usage.', '');
-- faculty_certifications — 3 row(s) for fac-ramanathan
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-ramanathan-cert-1', 'fac-ramanathan', 0, 'AWS Certified Solutions Architect – Associate', 'Professional Certification', 'Amazon Web Services', '2024', 'AWS-2024-8842', '', 'Professional certification covering secure, scalable cloud architecture design.', '');
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-ramanathan-cert-2', 'fac-ramanathan', 1, 'NPTEL Certification in Deep Learning', 'Training', 'IIT Madras', '2023', 'NPTEL/2023/DL/1124', '', 'Completed the advanced NPTEL programme with elite distinction.', '');
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-ramanathan-cert-3', 'fac-ramanathan', 2, 'Certified Data Science Professional', 'Professional Certification', 'IBM', '2022', 'IBM-DS-2022-5567', '', 'Credential focused on the end-to-end data science workflow and model deployment.', '');
-- faculty_responsibilities — 3 row(s) for fac-ramanathan
INSERT INTO `faculty_responsibilities` (`id`, `profile_id`, `sort_order`, `title`, `category`, `institution`, `position`, `level`, `from_month`, `to_month`, `is_current`) VALUES ('fac-ramanathan-resp-1', 'fac-ramanathan', 0, 'Head of Department', 'Department Responsibility', 'University College of Engineering Nagercoil', 'Professor & Head', 'Department Level', '2018-06', '', 1);
INSERT INTO `faculty_responsibilities` (`id`, `profile_id`, `sort_order`, `title`, `category`, `institution`, `position`, `level`, `from_month`, `to_month`, `is_current`) VALUES ('fac-ramanathan-resp-2', 'fac-ramanathan', 1, 'Member, Board of Studies in Computer Science', 'Academic Responsibility', 'Anna University, Chennai', 'Board Member', 'University Level', '2020-07', '', 1);
INSERT INTO `faculty_responsibilities` (`id`, `profile_id`, `sort_order`, `title`, `category`, `institution`, `position`, `level`, `from_month`, `to_month`, `is_current`) VALUES ('fac-ramanathan-resp-3', 'fac-ramanathan', 2, 'Nodal Officer, AICTE Faculty Development Programme', 'Institutional Responsibility', 'University College of Engineering Nagercoil', 'Nodal Officer', 'College Level', '2021-01', '2023-12', 0);

-- Profile: fac-anitha (Dr. M. Anitha)
INSERT INTO `faculty_profiles` (`id`, `name`, `faculty_id`, `department`, `designation`, `email`, `contact`, `specialization`, `photo_url`, `banner_url`, `research_guide_number`, `research_guide_university`, `research_guide_date`, `research_guide_status`, `phd_completed`, `mphil_completed`, `other_completed`, `phd_guiding`, `mphil_guiding`, `other_guiding`, `google_scholar_id`, `google_scholar_url`, `orcid_id`, `orcid_url`, `scopus_id`, `scopus_url`, `is_dean`, `display_order`, `created_at`, `updated_at`) VALUES ('fac-anitha', 'Dr. M. Anitha', 'UCEN-ECE-2018', 'Electronics and Communication Engineering', 'Associate Professor', 'm.anitha@ucen.ac.in', '+91 98421 33672', 'VLSI Design, Embedded Systems, Signal Processing', '', '', '', '', NULL, '', 0, 0, 0, 0, 0, 0, 'AnI7hXk2Qm4', 'https://scholar.google.com/citations?user=AnI7hXk2Qm4', '0000-0002-4471-8838', 'https://orcid.org/0000-0002-4471-8838', '57192184003', 'https://www.scopus.com/authid/detail.uri?authorId=57192184003', 0, 1, '2024-01-15T09:30:00.000Z', '2026-11-13T18:40:08.162Z');
-- faculty_qualifications — 2 row(s) for fac-anitha
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-anitha-qual-1', 'fac-anitha', 0, 'Ph.D. in Electronics Engineering', 'College of Engineering, Guindy', 'Anna University, Chennai', 2014, '', 'VLSI Design');
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-anitha-qual-2', 'fac-anitha', 1, 'M.E. in Applied Electronics', 'PSG College of Technology, Coimbatore', 'Anna University, Chennai', 2010, '', 'Embedded Systems');
-- faculty_experiences — 2 row(s) for fac-anitha
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-anitha-exp-1', 'fac-anitha', 0, 'University College of Engineering Nagercoil', 'Associate Professor', '2019-07', '', '');
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-anitha-exp-2', 'fac-anitha', 1, 'University College of Engineering Nagercoil', 'Assistant Professor', '2012-06', '2019-06', '');
-- faculty_publications — 2 row(s) for fac-anitha
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-anitha-pub-1', 'fac-anitha', 0, 'Sub-Threshold SRAM Design for Wearable Biomedical Sensors', 'Journal Article', 'Microelectronics Journal', '', 2023, '', '', '', '', '', '', 'https://doi.org/10.1016/j.mejo.2023.105622');
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-anitha-pub-2', 'fac-anitha', 1, 'A Reconfigurable FIR Filter for Low-Power Acoustic Sensing', 'Conference Paper', 'IEEE International Conference on VLSI Design', '', 2022, '', '', '', '', '', '', '');
-- faculty_projects — 1 row(s) for fac-anitha
INSERT INTO `faculty_projects` (`id`, `profile_id`, `sort_order`, `title`, `agency`, `category`, `principal_investigator`, `co_principal_investigator`, `faculty_role`, `duration`, `start_month`, `end_month`, `amount`, `status`, `reference`, `description`, `sanction_letter_url`) VALUES ('fac-anitha-prj-1', 'fac-anitha', 0, 'Low-Power VLSI Architectures for Wearable Health Monitors', 'Science and Engineering Research Board (DST)', '', '', '', 'Co-Investigator', '2021 – 2024', '', '', '1250000', 'Completed', '', 'Developed energy-efficient integrated circuits for continuous vital-sign monitoring in wearable devices.', '');
-- faculty_achievements — 1 row(s) for fac-anitha
INSERT INTO `faculty_achievements` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-anitha-ach-1', 'fac-anitha', 0, 'Best Paper Award – IEEE VLSI Design Conference', '', 'IEEE VLSI Society', '2022', '', '', 'Awarded for a paper on energy-efficient filtering architectures.', '');
-- faculty_certifications — 2 row(s) for fac-anitha
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-anitha-cert-1', 'fac-anitha', 0, 'NPTEL Certification in VLSI Design', '', 'IIT Bombay', '2023', '', '', 'Completed the advanced VLSI design programme with distinction.', '');
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-anitha-cert-2', 'fac-anitha', 1, 'Cadence Certified Digital Design Professional', '', 'Cadence Design Systems', '2021', '', '', 'Professional credential in digital IC design and verification.', '');

-- Profile: fac-priya (Dr. G. Priya)
INSERT INTO `faculty_profiles` (`id`, `name`, `faculty_id`, `department`, `designation`, `email`, `contact`, `specialization`, `photo_url`, `banner_url`, `research_guide_number`, `research_guide_university`, `research_guide_date`, `research_guide_status`, `phd_completed`, `mphil_completed`, `other_completed`, `phd_guiding`, `mphil_guiding`, `other_guiding`, `google_scholar_id`, `google_scholar_url`, `orcid_id`, `orcid_url`, `scopus_id`, `scopus_url`, `is_dean`, `display_order`, `created_at`, `updated_at`) VALUES ('fac-priya', 'Dr. G. Priya', 'UCEN-MBA-1007', 'MBA', 'Professor', 'g.priya@ucen.ac.in', '+91 98405 71234', 'Organisational Behaviour, Human Resource Management, Entrepreneurship', '', '', '', '', NULL, '', 0, 0, 0, 0, 0, 0, 'PrY9tLm4Rn2', 'https://scholar.google.com/citations?user=PrY9tLm4Rn2', '0000-0001-6620-1140', 'https://orcid.org/0000-0001-6620-1140', '57208441926', 'https://www.scopus.com/authid/detail.uri?authorId=57208441926', 0, 2, '2024-01-15T09:30:00.000Z', '2026-11-13T18:40:08.163Z');
-- faculty_qualifications — 2 row(s) for fac-priya
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-priya-qual-1', 'fac-priya', 0, 'Ph.D. in Management', 'Bharathiar University, Coimbatore', 'Bharathiar University', 2012, '', 'Organisational Behaviour');
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-priya-qual-2', 'fac-priya', 1, 'MBA in Human Resource Management', 'Madurai Kamaraj University', 'Madurai Kamaraj University', 2005, '', 'Human Resource Management');
-- faculty_experiences — 2 row(s) for fac-priya
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-priya-exp-1', 'fac-priya', 0, 'University College of Engineering Nagercoil', 'Professor', '2019-06', '', '');
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-priya-exp-2', 'fac-priya', 1, 'University College of Engineering Nagercoil', 'Associate Professor', '2012-06', '2019-05', '');
-- faculty_publications — 2 row(s) for fac-priya
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-priya-pub-1', 'fac-priya', 0, 'Workplace Flexibility and Employee Well-Being in Higher Education', 'Journal Article', 'South Asian Journal of Human Resources Management', '', 2024, '', '', '', '', '', '', 'https://doi.org/10.1177/2322093724123456');
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-priya-pub-2', 'fac-priya', 1, 'Building Entrepreneurial Intent among Rural Women', 'Journal Article', 'Journal of Entrepreneurship and Innovation in Emerging Economies', '', 2022, '', '', '', '', '', '', '');
-- faculty_projects — 1 row(s) for fac-priya
INSERT INTO `faculty_projects` (`id`, `profile_id`, `sort_order`, `title`, `agency`, `category`, `principal_investigator`, `co_principal_investigator`, `faculty_role`, `duration`, `start_month`, `end_month`, `amount`, `status`, `reference`, `description`, `sanction_letter_url`) VALUES ('fac-priya-prj-1', 'fac-priya', 0, 'Women Entrepreneurship Incubation Programme for Southern Tamil Nadu', 'AICTE – Entrepreneurship Development Cell', '', '', '', 'Project Coordinator', '2020 – 2023', '', '', '800000', 'Completed', '', 'Mentored 120 women-led enterprises through structured incubation, financial literacy and market-linkage support.', '');
-- faculty_achievements — 1 row(s) for fac-priya
INSERT INTO `faculty_achievements` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-priya-ach-1', 'fac-priya', 0, 'Excellence in Management Education Award', '', 'Indian Management Association', '2023', '', '', 'Recognised for contributions to inclusive entrepreneurship education.', '');
-- faculty_certifications — 2 row(s) for fac-priya
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-priya-cert-1', 'fac-priya', 0, 'Certified Professional in Talent Development', '', 'ATD', '2022', '', '', 'Credential covering learning strategy and talent development.', '');
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-priya-cert-2', 'fac-priya', 1, 'NPTEL Certification in Leadership and Team Effectiveness', '', 'IIT Roorkee', '2021', '', '', 'Advanced programme in leadership practice and team dynamics.', '');

-- Profile: fac-arunachalam (Dr. S. Arunachalam)
INSERT INTO `faculty_profiles` (`id`, `name`, `faculty_id`, `department`, `designation`, `email`, `contact`, `specialization`, `photo_url`, `banner_url`, `research_guide_number`, `research_guide_university`, `research_guide_date`, `research_guide_status`, `phd_completed`, `mphil_completed`, `other_completed`, `phd_guiding`, `mphil_guiding`, `other_guiding`, `google_scholar_id`, `google_scholar_url`, `orcid_id`, `orcid_url`, `scopus_id`, `scopus_url`, `is_dean`, `display_order`, `created_at`, `updated_at`) VALUES ('fac-arunachalam', 'Dr. S. Arunachalam', 'UCEN-MECH-3061', 'Mechanical Engineering', 'Assistant Professor', 's.arunachalam@ucen.ac.in', '+91 97863 44210', 'Thermal Engineering, Renewable Energy, Computational Fluid Dynamics', '', '', '', '', NULL, '', 0, 0, 0, 0, 0, 0, 'ArU3wQp8Tz5', 'https://scholar.google.com/citations?user=ArU3wQp8Tz5', '0000-0003-2190-5563', 'https://orcid.org/0000-0003-2190-5563', '56637201158', 'https://www.scopus.com/authid/detail.uri?authorId=56637201158', 0, 3, '2024-01-15T09:30:00.000Z', '2026-11-13T18:40:08.163Z');
-- faculty_qualifications — 2 row(s) for fac-arunachalam
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-arunachalam-qual-1', 'fac-arunachalam', 0, 'Ph.D. in Mechanical Engineering', 'Indian Institute of Technology Madras', 'IIT Madras', 2016, '', 'Thermal Engineering');
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-arunachalam-qual-2', 'fac-arunachalam', 1, 'M.E. in Thermal Engineering', 'Government College of Technology, Coimbatore', 'Anna University, Chennai', 2010, '', 'Energy Systems');
-- faculty_experiences — 2 row(s) for fac-arunachalam
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-arunachalam-exp-1', 'fac-arunachalam', 0, 'University College of Engineering Nagercoil', 'Assistant Professor', '2017-07', '', '');
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-arunachalam-exp-2', 'fac-arunachalam', 1, 'Sri Venkateswara College of Engineering, Chennai', 'Assistant Professor', '2010-06', '2017-06', '');
-- faculty_publications — 2 row(s) for fac-arunachalam
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-arunachalam-pub-1', 'fac-arunachalam', 0, 'Performance of a Phase-Change Energy Storage Unit for Solar Dryers', 'Journal Article', 'Solar Energy', '', 2024, '', '', '', '', '', '', 'https://doi.org/10.1016/j.solener.2024.112865');
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-arunachalam-pub-2', 'fac-arunachalam', 1, 'CFD Analysis of Jet Impingement Cooling in Electronic Enclosures', 'Journal Article', 'Applied Thermal Engineering', '', 2022, '', '', '', '', '', '', '');
-- faculty_projects — 1 row(s) for fac-arunachalam
INSERT INTO `faculty_projects` (`id`, `profile_id`, `sort_order`, `title`, `agency`, `category`, `principal_investigator`, `co_principal_investigator`, `faculty_role`, `duration`, `start_month`, `end_month`, `amount`, `status`, `reference`, `description`, `sanction_letter_url`) VALUES ('fac-arunachalam-prj-1', 'fac-arunachalam', 0, 'Solar Thermal Hybrid Dryer for Small-Scale Marine Food Processing', 'Science and Engineering Research Board (DST)', '', '', '', 'Principal Investigator', '2023 – 2026', '', '', '2200000', 'Ongoing', '', 'Designing a hybrid solar dryer to improve shelf life and quality for coastal food-processing enterprises.', '');
-- faculty_achievements — 1 row(s) for fac-arunachalam
INSERT INTO `faculty_achievements` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-arunachalam-ach-1', 'fac-arunachalam', 0, 'Young Researcher Award', '', 'Indian Society for Heat and Mass Transfer', '2023', '', '', 'Awarded for research on thermal energy storage for decentralised solar applications.', '');
-- faculty_certifications — 2 row(s) for fac-arunachalam
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-arunachalam-cert-1', 'fac-arunachalam', 0, 'Certified Energy Auditor', '', 'Bureau of Energy Efficiency, Government of India', '2022', '', '', 'Certified to conduct industrial and commercial energy audits.', '');
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-arunachalam-cert-2', 'fac-arunachalam', 1, 'NPTEL Certification in Computational Fluid Dynamics', '', 'IIT Kanpur', '2021', '', '', 'Advanced course covering finite-volume methods and turbulence modelling.', '');

-- Profile: fac-meenakshi (Dr. R. Meenakshi)
INSERT INTO `faculty_profiles` (`id`, `name`, `faculty_id`, `department`, `designation`, `email`, `contact`, `specialization`, `photo_url`, `banner_url`, `research_guide_number`, `research_guide_university`, `research_guide_date`, `research_guide_status`, `phd_completed`, `mphil_completed`, `other_completed`, `phd_guiding`, `mphil_guiding`, `other_guiding`, `google_scholar_id`, `google_scholar_url`, `orcid_id`, `orcid_url`, `scopus_id`, `scopus_url`, `is_dean`, `display_order`, `created_at`, `updated_at`) VALUES ('fac-meenakshi', 'Dr. R. Meenakshi', 'UCEN-IT-2088', 'Information Technology', 'Assistant Professor', 'r.meenakshi@ucen.ac.in', '+91 94431 50826', 'Data Science, Machine Learning, Natural Language Processing', '', '', '', '', NULL, '', 0, 0, 0, 0, 0, 0, 'MeE6sVb1Jc9', 'https://scholar.google.com/citations?user=MeE6sVb1Jc9', '0000-0002-8834-907X', 'https://orcid.org/0000-0002-8834-907X', '57211334087', 'https://www.scopus.com/authid/detail.uri?authorId=57211334087', 0, 4, '2024-01-15T09:30:00.000Z', '2026-11-13T18:40:08.163Z');
-- faculty_qualifications — 2 row(s) for fac-meenakshi
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-meenakshi-qual-1', 'fac-meenakshi', 0, 'Ph.D. in Information Technology', 'College of Engineering, Guindy', 'Anna University, Chennai', 2018, '', 'Machine Learning');
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-meenakshi-qual-2', 'fac-meenakshi', 1, 'M.Tech. in Information Technology', 'SSN College of Engineering, Chennai', 'Anna University, Chennai', 2011, '', 'Data Science');
-- faculty_experiences — 2 row(s) for fac-meenakshi
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-meenakshi-exp-1', 'fac-meenakshi', 0, 'University College of Engineering Nagercoil', 'Assistant Professor', '2018-07', '', '');
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-meenakshi-exp-2', 'fac-meenakshi', 1, 'SRM Institute of Science and Technology, Chennai', 'Assistant Professor', '2011-06', '2018-06', '');
-- faculty_publications — 2 row(s) for fac-meenakshi
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-meenakshi-pub-1', 'fac-meenakshi', 0, 'Low-Resource Tamil Named Entity Recognition with Transfer Learning', 'Journal Article', 'ACM Transactions on Asian and Low-Resource Language Information Processing', '', 2024, '', '', '', '', '', '', 'https://doi.org/10.1145/3648421');
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-meenakshi-pub-2', 'fac-meenakshi', 1, 'Explainable Machine Learning for Student Performance Prediction', 'Journal Article', 'Education and Information Technologies', '', 2023, '', '', '', '', '', '', '');
-- faculty_projects — 1 row(s) for fac-meenakshi
INSERT INTO `faculty_projects` (`id`, `profile_id`, `sort_order`, `title`, `agency`, `category`, `principal_investigator`, `co_principal_investigator`, `faculty_role`, `duration`, `start_month`, `end_month`, `amount`, `status`, `reference`, `description`, `sanction_letter_url`) VALUES ('fac-meenakshi-prj-1', 'fac-meenakshi', 0, 'Tamil Natural Language Processing Toolkit for Primary Healthcare', 'Science and Engineering Research Board (DST)', '', '', '', 'Principal Investigator', '2022 – 2025', '', '', '1650000', 'Ongoing', '', 'Building open linguistic resources and models that help frontline health workers access information in Tamil.', '');
-- faculty_achievements — 1 row(s) for fac-meenakshi
INSERT INTO `faculty_achievements` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-meenakshi-ach-1', 'fac-meenakshi', 0, 'Women in Data Science Research Award', '', 'WiDS Chennai', '2024', '', '', 'Recognised for contributions to low-resource language processing and inclusive AI.', '');
-- faculty_certifications — 2 row(s) for fac-meenakshi
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-meenakshi-cert-1', 'fac-meenakshi', 0, 'TensorFlow Developer Certificate', '', 'Google', '2023', '', '', 'Professional certification in building and deploying machine learning models.', '');
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-meenakshi-cert-2', 'fac-meenakshi', 1, 'NPTEL Certification in Natural Language Processing', '', 'IIT Kharagpur', '2022', '', '', 'Advanced programme covering statistical and neural language processing.', '');

-- Profile: fac-vignesh (Dr. P. Vignesh)
INSERT INTO `faculty_profiles` (`id`, `name`, `faculty_id`, `department`, `designation`, `email`, `contact`, `specialization`, `photo_url`, `banner_url`, `research_guide_number`, `research_guide_university`, `research_guide_date`, `research_guide_status`, `phd_completed`, `mphil_completed`, `other_completed`, `phd_guiding`, `mphil_guiding`, `other_guiding`, `google_scholar_id`, `google_scholar_url`, `orcid_id`, `orcid_url`, `scopus_id`, `scopus_url`, `is_dean`, `display_order`, `created_at`, `updated_at`) VALUES ('fac-vignesh', 'Dr. P. Vignesh', 'UCEN-CIVIL-3024', 'Civil Engineering', 'Associate Professor', 'p.vignesh@ucen.ac.in', '+91 99445 67218', 'Structural Engineering, Earthquake Resistant Design, Concrete Technology', '', '', '', '', NULL, '', 0, 0, 0, 0, 0, 0, 'ViG2kDn7Fh3', 'https://scholar.google.com/citations?user=ViG2kDn7Fh3', '0000-0001-9056-2389', 'https://orcid.org/0000-0001-9056-2389', '57194886210', 'https://www.scopus.com/authid/detail.uri?authorId=57194886210', 0, 5, '2024-01-15T09:30:00.000Z', '2026-11-13T18:40:08.163Z');
-- faculty_qualifications — 2 row(s) for fac-vignesh
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-vignesh-qual-1', 'fac-vignesh', 0, 'Ph.D. in Civil Engineering', 'National Institute of Technology Karnataka, Surathkal', 'NITK Surathkal', 2015, '', 'Structural Engineering');
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-vignesh-qual-2', 'fac-vignesh', 1, 'M.E. in Structural Engineering', 'Thiagarajar College of Engineering, Madurai', 'Anna University, Chennai', 2009, '', 'Structural Engineering');
-- faculty_experiences — 2 row(s) for fac-vignesh
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-vignesh-exp-1', 'fac-vignesh', 0, 'University College of Engineering Nagercoil', 'Associate Professor', '2018-06', '', '');
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-vignesh-exp-2', 'fac-vignesh', 1, 'University College of Engineering Nagercoil', 'Assistant Professor', '2009-07', '2018-05', '');
-- faculty_publications — 2 row(s) for fac-vignesh
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-vignesh-pub-1', 'fac-vignesh', 0, 'Retrofit Strategies for Reinforced Concrete School Buildings in Coastal Regions', 'Journal Article', 'Journal of Building Engineering', '', 2023, '', '', '', '', '', '', 'https://doi.org/10.1016/j.jobe.2023.106842');
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-vignesh-pub-2', 'fac-vignesh', 1, 'Durability of Sustainable Concrete with Industrial By-Products', 'Journal Article', 'Construction and Building Materials', '', 2021, '', '', '', '', '', '', '');
-- faculty_projects — 1 row(s) for fac-vignesh
INSERT INTO `faculty_projects` (`id`, `profile_id`, `sort_order`, `title`, `agency`, `category`, `principal_investigator`, `co_principal_investigator`, `faculty_role`, `duration`, `start_month`, `end_month`, `amount`, `status`, `reference`, `description`, `sanction_letter_url`) VALUES ('fac-vignesh-prj-1', 'fac-vignesh', 0, 'Seismic Performance Assessment of Coastal School Buildings', 'AICTE – Research Promotion Scheme', '', '', '', 'Principal Investigator', '2020 – 2023', '', '', '1400000', 'Completed', '', 'Assessed vulnerability and developed low-cost retrofit solutions for school buildings in coastal districts.', '');
-- faculty_achievements — 1 row(s) for fac-vignesh
INSERT INTO `faculty_achievements` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-vignesh-ach-1', 'fac-vignesh', 0, 'Outstanding Engineering Consultancy Award', '', 'Institution of Engineers (India)', '2022', '', '', 'Recognised for consultancy work on safe and sustainable public buildings.', '');
-- faculty_certifications — 2 row(s) for fac-vignesh
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-vignesh-cert-1', 'fac-vignesh', 0, 'Certified Structural Steel Designer', '', 'INSTRUCT', '2021', '', '', 'Professional certification in structural steel design.', '');
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-vignesh-cert-2', 'fac-vignesh', 1, 'NPTEL Certification in Earthquake Resistant Design', '', 'IIT Madras', '2020', '', '', 'Advanced programme on seismic design of buildings.', '');

-- Profile: fac-sharmila (Dr. L. Sharmila)
INSERT INTO `faculty_profiles` (`id`, `name`, `faculty_id`, `department`, `designation`, `email`, `contact`, `specialization`, `photo_url`, `banner_url`, `research_guide_number`, `research_guide_university`, `research_guide_date`, `research_guide_status`, `phd_completed`, `mphil_completed`, `other_completed`, `phd_guiding`, `mphil_guiding`, `other_guiding`, `google_scholar_id`, `google_scholar_url`, `orcid_id`, `orcid_url`, `scopus_id`, `scopus_url`, `is_dean`, `display_order`, `created_at`, `updated_at`) VALUES ('fac-sharmila', 'Dr. L. Sharmila', 'UCEN-SH-4012', 'Science and Humanities', 'Assistant Professor', 'l.sharmila@ucen.ac.in', '+91 95001 47853', 'Applied Mathematics, Numerical Analysis, Operations Research', '', '', '', '', NULL, '', 0, 0, 0, 0, 0, 0, 'ShA5cMx9Lp4', 'https://scholar.google.com/citations?user=ShA5cMx9Lp4', '0000-0002-3307-6617', 'https://orcid.org/0000-0002-3307-6617', '56021887334', 'https://www.scopus.com/authid/detail.uri?authorId=56021887334', 0, 6, '2024-01-15T09:30:00.000Z', '2026-11-13T18:40:08.163Z');
-- faculty_qualifications — 2 row(s) for fac-sharmila
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-sharmila-qual-1', 'fac-sharmila', 0, 'Ph.D. in Mathematics', 'Madurai Kamaraj University', 'Madurai Kamaraj University', 2017, '', 'Applied Mathematics');
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-sharmila-qual-2', 'fac-sharmila', 1, 'M.Sc. in Mathematics', 'Lady Doak College, Madurai', 'Madurai Kamaraj University', 2008, '', 'Numerical Analysis');
-- faculty_experiences — 2 row(s) for fac-sharmila
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-sharmila-exp-1', 'fac-sharmila', 0, 'University College of Engineering Nagercoil', 'Assistant Professor', '2016-07', '', '');
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-sharmila-exp-2', 'fac-sharmila', 1, 'St. Xavier''s College, Palayamkottai', 'Assistant Professor', '2009-06', '2016-06', '');
-- faculty_publications — 2 row(s) for fac-sharmila
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-sharmila-pub-1', 'fac-sharmila', 0, 'A Fractional-Order Model for Dengue Dynamics with Seasonal Forcing', 'Journal Article', 'Mathematics and Computers in Simulation', '', 2024, '', '', '', '', '', '', 'https://doi.org/10.1016/j.matcom.2024.104215');
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-sharmila-pub-2', 'fac-sharmila', 1, 'Optimisation of Supply Chains with Uncertain Demand', 'Journal Article', 'Opsearch', '', 2022, '', '', '', '', '', '', '');
-- faculty_projects — 1 row(s) for fac-sharmila
INSERT INTO `faculty_projects` (`id`, `profile_id`, `sort_order`, `title`, `agency`, `category`, `principal_investigator`, `co_principal_investigator`, `faculty_role`, `duration`, `start_month`, `end_month`, `amount`, `status`, `reference`, `description`, `sanction_letter_url`) VALUES ('fac-sharmila-prj-1', 'fac-sharmila', 0, 'Mathematical Modelling of Dengue Transmission in Coastal Districts', 'Tamil Nadu State Council for Science and Technology', '', '', '', 'Principal Investigator', '2023 – 2025', '', '', '600000', 'Ongoing', '', 'Developing predictive models to support vector-control planning and public health resource allocation.', '');
-- faculty_achievements — 1 row(s) for fac-sharmila
INSERT INTO `faculty_achievements` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-sharmila-ach-1', 'fac-sharmila', 0, 'Best Young Mathematician Award', '', 'Indian Mathematical Society', '2023', '', '', 'Awarded for work on fractional-order epidemic models.', '');
-- faculty_certifications — 2 row(s) for fac-sharmila
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-sharmila-cert-1', 'fac-sharmila', 0, 'NPTEL Certification in Operations Research', '', 'IIT Madras', '2022', '', '', 'Advanced programme in linear and integer programming.', '');
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-sharmila-cert-2', 'fac-sharmila', 1, 'Python for Scientific Computing', '', 'Coursera', '2021', '', '', 'Professional certificate in numerical computing with Python.', '');

-- Profile: fac-jebasingh (Dr. A. Jebasingh)
INSERT INTO `faculty_profiles` (`id`, `name`, `faculty_id`, `department`, `designation`, `email`, `contact`, `specialization`, `photo_url`, `banner_url`, `research_guide_number`, `research_guide_university`, `research_guide_date`, `research_guide_status`, `phd_completed`, `mphil_completed`, `other_completed`, `phd_guiding`, `mphil_guiding`, `other_guiding`, `google_scholar_id`, `google_scholar_url`, `orcid_id`, `orcid_url`, `scopus_id`, `scopus_url`, `is_dean`, `display_order`, `created_at`, `updated_at`) VALUES ('fac-jebasingh', 'Dr. A. Jebasingh', 'UCEN-EEE-1055', 'Electrical and Electronics Engineering', 'Professor', 'a.jebasingh@ucen.ac.in', '+91 94433 62901', 'Power Systems, Smart Grid, Power Electronics', '', '', '', '', NULL, '', 0, 0, 0, 0, 0, 0, 'JeB8rYt3Wk6', 'https://scholar.google.com/citations?user=JeB8rYt3Wk6', '0000-0003-7742-1191', 'https://orcid.org/0000-0003-7742-1191', '57200563142', 'https://www.scopus.com/authid/detail.uri?authorId=57200563142', 0, 7, '2024-01-15T09:30:00.000Z', '2026-11-13T18:40:08.163Z');
-- faculty_qualifications — 2 row(s) for fac-jebasingh
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-jebasingh-qual-1', 'fac-jebasingh', 0, 'Ph.D. in Electrical Engineering', 'Indian Institute of Technology Delhi', 'IIT Delhi', 2011, '', 'Power Systems');
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-jebasingh-qual-2', 'fac-jebasingh', 1, 'M.E. in Power Systems Engineering', 'College of Engineering, Guindy', 'Anna University, Chennai', 2004, '', 'Power Systems');
-- faculty_experiences — 2 row(s) for fac-jebasingh
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-jebasingh-exp-1', 'fac-jebasingh', 0, 'University College of Engineering Nagercoil', 'Professor', '2018-07', '', '');
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-jebasingh-exp-2', 'fac-jebasingh', 1, 'University College of Engineering Nagercoil', 'Associate Professor', '2011-06', '2018-06', '');
-- faculty_publications — 2 row(s) for fac-jebasingh
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-jebasingh-pub-1', 'fac-jebasingh', 0, 'Coordinated Voltage Control for Distributed Solar PV in Low-Voltage Networks', 'Journal Article', 'IEEE Transactions on Smart Grid', '', 2024, '', '', '', '', '', '', 'https://doi.org/10.1109/TSG.2024.3387124');
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-jebasingh-pub-2', 'fac-jebasingh', 1, 'Model Predictive Control of a Bidirectional Converter', 'Journal Article', 'IEEE Transactions on Power Electronics', '', 2022, '', '', '', '', '', '', '');
-- faculty_projects — 1 row(s) for fac-jebasingh
INSERT INTO `faculty_projects` (`id`, `profile_id`, `sort_order`, `title`, `agency`, `category`, `principal_investigator`, `co_principal_investigator`, `faculty_role`, `duration`, `start_month`, `end_month`, `amount`, `status`, `reference`, `description`, `sanction_letter_url`) VALUES ('fac-jebasingh-prj-1', 'fac-jebasingh', 0, 'Smart Grid Testbed for Distributed Solar Integration in Southern Tamil Nadu', 'Department of Science and Technology', '', '', '', 'Principal Investigator', '2021 – 2024', '', '', '3200000', 'Completed', '', 'Built a laboratory testbed for studying voltage stability and coordinated control under high solar penetration.', '');
-- faculty_achievements — 1 row(s) for fac-jebasingh
INSERT INTO `faculty_achievements` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-jebasingh-ach-1', 'fac-jebasingh', 0, 'Fellow of the Institution of Engineers', '', 'Institution of Engineers (India)', '2023', '', '', 'Elected Fellow for contributions to power system research and education.', '');
-- faculty_certifications — 2 row(s) for fac-jebasingh
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-jebasingh-cert-1', 'fac-jebasingh', 0, 'Certified Power System Protection Engineer', '', 'ABB University', '2022', '', '', 'Advanced certification in protection coordination and relay settings.', '');
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-jebasingh-cert-2', 'fac-jebasingh', 1, 'NPTEL Certification in Smart Grid Technology', '', 'IIT Kharagpur', '2021', '', '', 'Programme covering smart grid architecture, communication and control.', '');

-- Profile: fac-kavitha (Dr. N. Kavitha)
INSERT INTO `faculty_profiles` (`id`, `name`, `faculty_id`, `department`, `designation`, `email`, `contact`, `specialization`, `photo_url`, `banner_url`, `research_guide_number`, `research_guide_university`, `research_guide_date`, `research_guide_status`, `phd_completed`, `mphil_completed`, `other_completed`, `phd_guiding`, `mphil_guiding`, `other_guiding`, `google_scholar_id`, `google_scholar_url`, `orcid_id`, `orcid_url`, `scopus_id`, `scopus_url`, `is_dean`, `display_order`, `created_at`, `updated_at`) VALUES ('fac-kavitha', 'Dr. N. Kavitha', 'UCEN-CSE-2033', 'Computer Science and Engineering', 'Associate Professor', 'n.kavitha@ucen.ac.in', '+91 90031 78426', 'Cyber Security, Blockchain, Internet of Things', '', '', '', '', NULL, '', 0, 0, 0, 0, 0, 0, 'KaV4nBq6Zd2', 'https://scholar.google.com/citations?user=KaV4nBq6Zd2', '0000-0001-4488-7254', 'https://orcid.org/0000-0001-4488-7254', '57186299047', 'https://www.scopus.com/authid/detail.uri?authorId=57186299047', 0, 8, '2024-01-15T09:30:00.000Z', '2026-11-13T18:40:08.163Z');
-- faculty_qualifications — 2 row(s) for fac-kavitha
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-kavitha-qual-1', 'fac-kavitha', 0, 'Ph.D. in Computer Science and Engineering', 'College of Engineering, Guindy', 'Anna University, Chennai', 2016, '', 'Cyber Security');
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-kavitha-qual-2', 'fac-kavitha', 1, 'M.E. in Computer Science and Engineering', 'Government College of Engineering, Tirunelveli', 'Anna University, Chennai', 2009, '', 'Information Security');
-- faculty_experiences — 2 row(s) for fac-kavitha
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-kavitha-exp-1', 'fac-kavitha', 0, 'University College of Engineering Nagercoil', 'Associate Professor', '2020-06', '', '');
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-kavitha-exp-2', 'fac-kavitha', 1, 'University College of Engineering Nagercoil', 'Assistant Professor', '2010-07', '2020-05', '');
-- faculty_publications — 2 row(s) for fac-kavitha
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-kavitha-pub-1', 'fac-kavitha', 0, 'A Permissioned Blockchain Architecture for Public Land Records', 'Journal Article', 'Journal of Network and Computer Applications', '', 2024, '', '', '', '', '', '', 'https://doi.org/10.1016/j.jnca.2024.103928');
INSERT INTO `faculty_publications` (`id`, `profile_id`, `sort_order`, `title`, `type`, `venue`, `authors`, `year`, `volume`, `issue`, `pages`, `publisher`, `indexing`, `doi`, `url`) VALUES ('fac-kavitha-pub-2', 'fac-kavitha', 1, 'Lightweight Authentication for Resource-Constrained IoT Devices', 'Journal Article', 'Ad Hoc Networks', '', 2023, '', '', '', '', '', '', '');
-- faculty_projects — 1 row(s) for fac-kavitha
INSERT INTO `faculty_projects` (`id`, `profile_id`, `sort_order`, `title`, `agency`, `category`, `principal_investigator`, `co_principal_investigator`, `faculty_role`, `duration`, `start_month`, `end_month`, `amount`, `status`, `reference`, `description`, `sanction_letter_url`) VALUES ('fac-kavitha-prj-1', 'fac-kavitha', 0, 'Blockchain-Based Tamper-Proof Land Record Management for Rural Administration', 'Ministry of Electronics and Information Technology (MeitY)', '', '', '', 'Principal Investigator', '2022 – 2025', '', '', '2500000', 'Ongoing', '', 'Prototyping a permissioned blockchain platform for transparent and auditable land records in village administration.', '');
-- faculty_achievements — 1 row(s) for fac-kavitha
INSERT INTO `faculty_achievements` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-kavitha-ach-1', 'fac-kavitha', 0, 'Cyber Security Educator of the Year', '', 'Cyber Society of India', '2023', '', '', 'Recognised for building a strong undergraduate security research pipeline.', '');
-- faculty_certifications — 2 row(s) for fac-kavitha
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-kavitha-cert-1', 'fac-kavitha', 0, 'Certified Information Systems Security Professional (CISSP)', '', 'ISC²', '2022', '', '', 'Professional certification in information security governance and architecture.', '');
INSERT INTO `faculty_certifications` (`id`, `profile_id`, `sort_order`, `title`, `category`, `organization`, `year`, `certificate_number`, `certificate_url`, `description`, `certificate_file_url`) VALUES ('fac-kavitha-cert-2', 'fac-kavitha', 1, 'NPTEL Certification in Blockchain Technology', '', 'IIT Madras', '2021', '', '', 'Advanced programme on distributed ledgers and consensus mechanisms.', '');

-- Profile: fac-ananya (Dr. Ananya Nair)
INSERT INTO `faculty_profiles` (`id`, `name`, `faculty_id`, `department`, `designation`, `email`, `contact`, `specialization`, `photo_url`, `banner_url`, `research_guide_number`, `research_guide_university`, `research_guide_date`, `research_guide_status`, `phd_completed`, `mphil_completed`, `other_completed`, `phd_guiding`, `mphil_guiding`, `other_guiding`, `google_scholar_id`, `google_scholar_url`, `orcid_id`, `orcid_url`, `scopus_id`, `scopus_url`, `is_dean`, `display_order`, `created_at`, `updated_at`) VALUES ('fac-ananya', 'Dr. Ananya Nair', 'UCEN-IT-2110', 'Information Technology', 'Assistant Professor', NULL, '', 'Human-Computer Interaction, Learning Analytics', '', '', '', '', NULL, '', 0, 0, 0, 0, 0, 0, 'AnA7fWj5Sc8', 'https://scholar.google.com/citations?user=AnA7fWj5Sc8', '0000-0002-6019-3343', 'https://orcid.org/0000-0002-6019-3343', '57213990465', 'https://www.scopus.com/authid/detail.uri?authorId=57213990465', 0, 9, '2024-01-15T09:30:00.000Z', '2026-11-13T18:40:08.163Z');

-- Profile: fac-vishal (Mr. Vishal Menon)
INSERT INTO `faculty_profiles` (`id`, `name`, `faculty_id`, `department`, `designation`, `email`, `contact`, `specialization`, `photo_url`, `banner_url`, `research_guide_number`, `research_guide_university`, `research_guide_date`, `research_guide_status`, `phd_completed`, `mphil_completed`, `other_completed`, `phd_guiding`, `mphil_guiding`, `other_guiding`, `google_scholar_id`, `google_scholar_url`, `orcid_id`, `orcid_url`, `scopus_id`, `scopus_url`, `is_dean`, `display_order`, `created_at`, `updated_at`) VALUES ('fac-vishal', 'Mr. Vishal Menon', 'UCEN-ECE-2118', 'Electronics and Communication Engineering', 'Assistant Professor', 'v.menon@ucen.ac.in', '+91 90032 55718', 'VLSI Design, Embedded Systems', '', '', '', '', NULL, '', 0, 0, 0, 0, 0, 0, 'ViS1pGm2Hd7', 'https://scholar.google.com/citations?user=ViS1pGm2Hd7', '0000-0001-2374-889X', 'https://orcid.org/0000-0001-2374-889X', '57199234618', 'https://www.scopus.com/authid/detail.uri?authorId=57199234618', 0, 10, '2024-01-15T09:30:00.000Z', '2026-11-13T18:40:08.163Z');
-- faculty_qualifications — 1 row(s) for fac-vishal
INSERT INTO `faculty_qualifications` (`id`, `profile_id`, `sort_order`, `degree`, `institution`, `university`, `year`, `class_obtained`, `specialization`) VALUES ('fac-vishal-qual-1', 'fac-vishal', 0, 'M.E. in VLSI Design', 'College of Engineering, Guindy', 'Anna University, Chennai', 2021, '', 'VLSI Design');
-- faculty_experiences — 1 row(s) for fac-vishal
INSERT INTO `faculty_experiences` (`id`, `profile_id`, `sort_order`, `institution`, `designation`, `from_month`, `to_month`, `total_experience`) VALUES ('fac-vishal-exp-1', 'fac-vishal', 0, 'University College of Engineering Nagercoil', 'Assistant Professor', '2022-07', '', '');

-- Profile: fac-dean (Dr.T Sree Renga Raja)
INSERT INTO `faculty_profiles` (`id`, `name`, `faculty_id`, `department`, `designation`, `email`, `contact`, `specialization`, `photo_url`, `banner_url`, `research_guide_number`, `research_guide_university`, `research_guide_date`, `research_guide_status`, `phd_completed`, `mphil_completed`, `other_completed`, `phd_guiding`, `mphil_guiding`, `other_guiding`, `google_scholar_id`, `google_scholar_url`, `orcid_id`, `orcid_url`, `scopus_id`, `scopus_url`, `is_dean`, `display_order`, `created_at`, `updated_at`) VALUES ('fac-dean', 'Dr.T Sree Renga Raja', NULL, '', 'Dean & Professor', 'dean@ucen.ac.in', '', '', '', '', '', '', NULL, '', 0, 0, 0, 0, 0, 0, 'SrR6tKc4Bv9', 'https://scholar.google.com/citations?user=SrR6tKc4Bv9', '0000-0002-9150-447X', 'https://orcid.org/0000-0002-9150-447X', '70060188255', 'https://www.scopus.com/authid/detail.uri?authorId=70060188255', 1, 11, '2024-01-15T09:30:00.000Z', '2026-11-13T18:40:08.163Z');

-- Seed summary: 12 faculty profile(s).
--   faculty_qualifications: 20 row(s)
--   faculty_experiences: 20 row(s)
--   faculty_publications: 20 row(s)
--   faculty_projects: 11 row(s)
--   faculty_conferences_attended: 3 row(s)
--   faculty_conferences_conducted: 2 row(s)
--   faculty_training_programs: 2 row(s)
--   faculty_patents: 2 row(s)
--   faculty_research_areas: 3 row(s)
--   faculty_achievements: 11 row(s)
--   faculty_certifications: 19 row(s)
--   faculty_responsibilities: 3 row(s)

-- -----------------------------------------------------------------------------
-- 4. Migration: staff/admin ID authentication (2026-11-15)
--    Source: backend/db/migrations/2026-11-15-staff-admin-auth.sql
--    Adds the staff_id / admin_id / department columns and their unique keys if
--    missing (already present in the schema above), creates the validuser and
--    validadmin whitelist tables, seeds them, and backfills legacy accounts.
--    Safe to re-run: every statement is idempotent.
-- -----------------------------------------------------------------------------
-- UCEN Faculty Portal — staff/admin ID authentication
--
-- Mirrors the production auth pattern (see the college-website repo):
--   * staff sign in with a staff ID (users.staff_id) gated by validuser,
--   * administrators sign in with an admin ID (users.admin_id) or email,
--     gated by validadmin.
--
-- Safe to run more than once: the ALTER uses a helper procedure so a missing
-- column is added and an existing one is skipped, and the seeds are idempotent.
--
--   C:\xampp\mysql\bin\mysql.exe -u root ucen_portal < backend/db/migrations/2026-11-15-staff-admin-auth.sql

DROP PROCEDURE IF EXISTS ucen_add_column_if_missing;

DELIMITER //
CREATE PROCEDURE ucen_add_column_if_missing(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

CALL ucen_add_column_if_missing('users', 'staff_id', 'VARCHAR(190) NULL AFTER `name`');
CALL ucen_add_column_if_missing('users', 'admin_id', 'VARCHAR(190) NULL AFTER `staff_id`');
CALL ucen_add_column_if_missing('users', 'department', 'VARCHAR(160) NOT NULL DEFAULT '''' AFTER `admin_id`');

DROP PROCEDURE IF EXISTS ucen_add_column_if_missing;

-- Unique keys (ignore if they already exist).
SET @has_key = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uniq_users_staff_id');
SET @sql = IF(@has_key = 0,
    'ALTER TABLE `users` ADD UNIQUE KEY `uniq_users_staff_id` (`staff_id`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_key = (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uniq_users_admin_id');
SET @sql = IF(@has_key = 0,
    'ALTER TABLE `users` ADD UNIQUE KEY `uniq_users_admin_id` (`admin_id`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS validuser (
  staff_id   VARCHAR(190) NOT NULL,
  name       VARCHAR(255) NULL,
  created_at VARCHAR(40)  NULL,
  PRIMARY KEY (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS validadmin (
  admin_id   VARCHAR(190) NOT NULL,
  name       VARCHAR(255) NULL,
  created_at VARCHAR(40)  NULL,
  PRIMARY KEY (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Seed whitelists ────────────────────────────────────────────────────────
-- Every published faculty profile contributes its faculty_id as a staff ID.
INSERT IGNORE INTO validuser (staff_id, name, created_at)
SELECT faculty_id, name, DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%s+05:30')
FROM faculty_profiles
WHERE faculty_id IS NOT NULL AND faculty_id <> '';

INSERT IGNORE INTO validadmin (admin_id, name, created_at) VALUES
  ('admin',     'Administrator', DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%s+05:30')),
  ('registrar', 'Registrar',     DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%s+05:30'));

-- ─── Backfill existing accounts ─────────────────────────────────────────────
UPDATE users SET admin_id = 'admin'     WHERE email = 'admin@ucen.ac.in'     AND (admin_id IS NULL OR admin_id = '');
UPDATE users SET admin_id = 'registrar' WHERE email = 'registrar@ucen.ac.in' AND (admin_id IS NULL OR admin_id = '');
UPDATE users SET staff_id = 'UCEN-CSE-1042' WHERE email = 'ramanathan@ucen.ac.in' AND (staff_id IS NULL OR staff_id = '');
UPDATE users SET staff_id = 'UCEN-EEE-1055' WHERE email = 'jebasingh@ucen.ac.in' AND (staff_id IS NULL OR staff_id = '');

UPDATE users u
JOIN faculty_profiles f ON f.id = u.faculty_profile_id
SET u.department = f.department
WHERE u.role = 'faculty' AND (u.department IS NULL OR u.department = '') AND f.department <> '';


-- -----------------------------------------------------------------------------
-- 5. Verification — quick post-restore sanity checks
-- -----------------------------------------------------------------------------
-- SELECT COUNT(*) AS profiles FROM faculty_profiles;                 -- expect 12
-- SELECT COUNT(*) AS deans FROM faculty_profiles WHERE is_dean = 1;  -- expect 1
-- SELECT table_name, table_rows FROM information_schema.tables
--   WHERE table_schema = DATABASE() ORDER BY table_name;
-- SELECT id, name, faculty_id, department, is_dean
--   FROM faculty_profiles ORDER BY display_order, name;
-- SELECT staff_id, name FROM validuser ORDER BY staff_id;
-- SELECT admin_id, name FROM validadmin ORDER BY admin_id;

SET FOREIGN_KEY_CHECKS = 1;

-- End of dump.
