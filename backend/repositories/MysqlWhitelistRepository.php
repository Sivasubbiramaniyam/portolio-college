<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDOException;

/**
 * MySQL whitelist provider (validuser / validadmin).
 *
 * The stored name is authoritative: a self-registering staff member cannot
 * choose their own display name, it comes from the pre-approved row.
 */
final class MysqlWhitelistRepository implements WhitelistRepositoryInterface
{
    use TranslatesDriverErrors;

    public function __construct(private Database $db)
    {
    }

    public function isConfigured(): bool
    {
        try {
            return (int) $this->db->scalar('SELECT COUNT(*) FROM validuser') > 0;
        } catch (PDOException) {
            return false;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function allStaff(): array
    {
        $rows = $this->db->select('SELECT staff_id, name, created_at FROM validuser ORDER BY staff_id ASC');

        return array_map(
            static fn (array $row): array => [
                'id' => (string) ($row['staff_id'] ?? ''),
                'name' => (string) ($row['name'] ?? ''),
                'createdAt' => $row['created_at'] === null ? null : (string) $row['created_at'],
            ],
            $rows
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function allAdmins(): array
    {
        $rows = $this->db->select('SELECT admin_id, name, created_at FROM validadmin ORDER BY admin_id ASC');

        return array_map(
            static fn (array $row): array => [
                'id' => (string) ($row['admin_id'] ?? ''),
                'name' => (string) ($row['name'] ?? ''),
                'createdAt' => $row['created_at'] === null ? null : (string) $row['created_at'],
            ],
            $rows
        );
    }

    /** @return array<string, mixed>|null */
    public function findStaff(string $staffId): ?array
    {
        $staffId = trim($staffId);
        if ($staffId === '') {
            return null;
        }

        $row = $this->db->selectOne('SELECT staff_id, name, created_at FROM validuser WHERE staff_id = ? LIMIT 1', [$staffId]);
        if ($row === null) {
            return null;
        }

        return [
            'id' => (string) ($row['staff_id'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'createdAt' => $row['created_at'] === null ? null : (string) $row['created_at'],
        ];
    }

    /** @return array<string, mixed>|null */
    public function findAdmin(string $adminId): ?array
    {
        $adminId = trim($adminId);
        if ($adminId === '') {
            return null;
        }

        $row = $this->db->selectOne('SELECT admin_id, name, created_at FROM validadmin WHERE admin_id = ? LIMIT 1', [$adminId]);
        if ($row === null) {
            return null;
        }

        return [
            'id' => (string) ($row['admin_id'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'createdAt' => $row['created_at'] === null ? null : (string) $row['created_at'],
        ];
    }
}
