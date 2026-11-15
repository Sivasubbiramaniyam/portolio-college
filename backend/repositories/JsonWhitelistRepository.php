<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\JsonStore;

/**
 * JSON whitelist provider used when DATA_DRIVER=json.
 *
 * Expected shape:
 *   { "staff": [{"id": "...", "name": "..."}], "admins": [{"id": "...", "name": "..."}] }
 */
final class JsonWhitelistRepository implements WhitelistRepositoryInterface
{
    private JsonStore $store;

    public function __construct(string $path)
    {
        $this->store = new JsonStore($path);
    }

    public function isConfigured(): bool
    {
        return $this->allStaff() !== [] || $this->allAdmins() !== [];
    }

    /** @return array<int, array<string, mixed>> */
    public function allStaff(): array
    {
        return $this->rows('staff');
    }

    /** @return array<int, array<string, mixed>> */
    public function allAdmins(): array
    {
        return $this->rows('admins');
    }

    /** @return array<string, mixed>|null */
    public function findStaff(string $staffId): ?array
    {
        return $this->find('staff', $staffId);
    }

    /** @return array<string, mixed>|null */
    public function findAdmin(string $adminId): ?array
    {
        return $this->find('admins', $adminId);
    }

    /** @return array<int, array<string, mixed>> */
    private function rows(string $key): array
    {
        $data = $this->store->read();
        $rows = [];
        foreach ((array) ($data[$key] ?? []) as $row) {
            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /** @return array<string, mixed>|null */
    private function find(string $key, string $id): ?array
    {
        $id = trim($id);
        if ($id === '') {
            return null;
        }
        foreach ($this->rows($key) as $row) {
            if ((string) ($row['id'] ?? '') === $id) {
                return $row;
            }
        }

        return null;
    }
}
