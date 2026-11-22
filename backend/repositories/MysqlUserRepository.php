<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\ApiException;
use App\Core\Database;
use PDOException;

/**
 * MySQL authentication user provider.
 *
 * Mirrors JsonUserRepository: emails are compared case-insensitively (the
 * column collation is utf8mb4_unicode_ci) and password hashes are stored as-is.
 * Users are created with backend/bin/make-user.php.
 */
final class MysqlUserRepository implements UserRepositoryInterface
{
    use TranslatesDriverErrors;

    /** API field => database column. */
    private const FIELDS = [
        'id' => 'id',
        'email' => 'email',
        'name' => 'name',
        'staffId' => 'staff_id',
        'adminId' => 'admin_id',
        'department' => 'department',
        'passwordHash' => 'password_hash',
        'role' => 'role',
        'facultyProfileId' => 'faculty_profile_id',
        'isActive' => 'is_active',
        'lastLoginAt' => 'last_login_at',
        'createdAt' => 'created_at',
        'updatedAt' => 'updated_at',
    ];

    public function __construct(private Database $db)
    {
    }

    public function isConfigured(): bool
    {
        try {
            return (int) $this->db->scalar('SELECT COUNT(*) FROM users') > 0;
        } catch (PDOException) {
            // Table missing or database unavailable: treat as "not configured"
            // so /health degrades instead of returning a 500.
            return false;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $rows = $this->db->select('SELECT * FROM users ORDER BY created_at ASC, id ASC');

        return array_map(fn (array $row): array => $this->hydrate($row), $rows);
    }

    /** @return array<string, mixed>|null */
    public function findById(string $id): ?array
    {
        $row = $this->db->selectOne('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);

        return $row === null ? null : $this->hydrate($row);
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        $row = $this->db->selectOne('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);

        return $row === null ? null : $this->hydrate($row);
    }

    /** @return array<string, mixed>|null */
    public function findByStaffId(string $staffId): ?array
    {
        $staffId = trim($staffId);
        if ($staffId === '') {
            return null;
        }

        $row = $this->db->selectOne('SELECT * FROM users WHERE staff_id = ? LIMIT 1', [$staffId]);

        return $row === null ? null : $this->hydrate($row);
    }

    /** @return array<string, mixed>|null */
    public function findByAdminId(string $adminId): ?array
    {
        $adminId = trim($adminId);
        if ($adminId === '') {
            return null;
        }

        $row = $this->db->selectOne('SELECT * FROM users WHERE admin_id = ? LIMIT 1', [$adminId]);

        return $row === null ? null : $this->hydrate($row);
    }

    /** @param array<string, mixed> $user */
    public function create(array $user): array
    {
        $email = strtolower(trim((string) ($user['email'] ?? '')));
        if ($email === '') {
            throw new ApiException('VALIDATION_ERROR', 'Email is required.', 422, ['email' => 'Email is required.']);
        }

        if ($this->findByEmail($email) !== null) {
            throw ApiException::conflict('A user with this email already exists.');
        }

        $staffId = trim((string) ($user['staffId'] ?? ''));
        if ($staffId !== '' && $this->findByStaffId($staffId) !== null) {
            throw ApiException::conflict('A user with this staff ID already exists.');
        }

        $adminId = trim((string) ($user['adminId'] ?? ''));
        if ($adminId !== '' && $this->findByAdminId($adminId) !== null) {
            throw ApiException::conflict('A user with this admin ID already exists.');
        }

        $user['email'] = $email;
        $user['staffId'] = $staffId === '' ? null : $staffId;
        $user['adminId'] = $adminId === '' ? null : $adminId;
        $values = $this->values($user);

        $sql = 'INSERT INTO users (' . implode(', ', array_keys($values)) . ')'
            . ' VALUES (' . Database::placeholders($values) . ')';

        try {
            $this->db->execute($sql, array_values($values));
        } catch (PDOException $exception) {
            if (self::isDuplicateKey($exception)) {
                throw ApiException::conflict('A user with this email already exists.');
            }
            throw $exception;
        }

        return $this->findById((string) $values['id']) ?? $user;
    }

    public function touchLastLogin(string $id): void
    {
        $now = gmdate('c');
        $this->db->execute(
            'UPDATE users SET last_login_at = ?, updated_at = ? WHERE id = ?',
            [$now, $now, $id]
        );
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    private function hydrate(array $row): array
    {
        $user = [];
        foreach (self::FIELDS as $api => $column) {
            $value = $row[$column] ?? null;
            if ($api === 'isActive') {
                $user[$api] = ((int) $value) === 1;
            } elseif (in_array($api, ['facultyProfileId', 'staffId', 'adminId'], true)) {
                $user[$api] = ($value === null || $value === '') ? null : (string) $value;
            } elseif (in_array($api, ['lastLoginAt', 'createdAt', 'updatedAt'], true)) {
                $user[$api] = $value === null ? null : (string) $value;
            } else {
                $user[$api] = $value === null ? '' : (string) $value;
            }
        }

        return $user;
    }

    /** @param array<string, mixed> $user
     *  @return array<string, mixed>
     */
    private function values(array $user): array
    {
        $values = [];
        foreach (self::FIELDS as $api => $column) {
            $value = $user[$api] ?? null;

            if ($api === 'isActive') {
                $values[$column] = ($value === null || $value === true) ? 1 : (int) (bool) $value;
                continue;
            }
            if (in_array($api, ['facultyProfileId', 'staffId', 'adminId'], true)) {
                $text = trim((string) ($value ?? ''));
                $values[$column] = $text === '' ? null : $text;
                continue;
            }
            if ($api === 'createdAt' && ($value === null || $value === '')) {
                $values[$column] = gmdate('c');
                continue;
            }
            if (in_array($api, ['lastLoginAt', 'updatedAt'], true) && ($value === null || $value === '')) {
                $values[$column] = null;
                continue;
            }

            $values[$column] = (string) ($value ?? '');
        }

        return $values;
    }
}
