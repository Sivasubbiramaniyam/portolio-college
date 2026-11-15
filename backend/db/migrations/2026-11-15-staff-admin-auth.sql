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
