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
CREATE TABLE IF NOT EXISTS users (
  id                 VARCHAR(64)  NOT NULL,
  email              VARCHAR(190) NOT NULL,
  name               VARCHAR(190) NULL,
  password_hash      VARCHAR(255) NOT NULL,
  role               VARCHAR(20)  NOT NULL,
  faculty_profile_id VARCHAR(80)  NULL,
  is_active          TINYINT(1)   NOT NULL DEFAULT 1,
  last_login_at      VARCHAR(40)  NULL,
  created_at         VARCHAR(40)  NULL,
  updated_at         VARCHAR(40)  NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_users_email (email),
  UNIQUE KEY uniq_users_faculty (faculty_profile_id),
  CONSTRAINT fk_users_profile FOREIGN KEY (faculty_profile_id)
    REFERENCES faculty_profiles (id) ON DELETE SET NULL
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
