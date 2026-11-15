<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\ApiException;
use App\Core\JsonStore;

/**
 * JSON authentication user provider. Users are created explicitly with
 * backend/bin/make-user.php; no users file is shipped with the project.
 */
final class JsonUserRepository implements UserRepositoryInterface
{
    private JsonStore $store;

    public function __construct(string $path)
    {
        $this->store = new JsonStore($path);
    }

    public function isConfigured(): bool
    {
        return $this->store->exists() && $this->all() !== [];
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $data = $this->store->read();
        $users = [];
        foreach (($data['users'] ?? []) as $user) {
            if (is_array($user)) {
                $users[] = $user;
            }
        }

        return $users;
    }

    /** @return array<string, mixed>|null */
    public function findById(string $id): ?array
    {
        foreach ($this->all() as $user) {
            if ((string) ($user['id'] ?? '') === $id) {
                return $user;
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        foreach ($this->all() as $user) {
            if (strtolower((string) ($user['email'] ?? '')) === $email) {
                return $user;
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    public function findByStaffId(string $staffId): ?array
    {
        $staffId = trim($staffId);
        if ($staffId === '') {
            return null;
        }
        foreach ($this->all() as $user) {
            if ((string) ($user['staffId'] ?? '') === $staffId) {
                return $user;
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    public function findByAdminId(string $adminId): ?array
    {
        $adminId = trim($adminId);
        if ($adminId === '') {
            return null;
        }
        foreach ($this->all() as $user) {
            if ((string) ($user['adminId'] ?? '') === $adminId) {
                return $user;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $user */
    public function create(array $user): array
    {
        $email = strtolower(trim((string) ($user['email'] ?? '')));
        if ($email === '') {
            throw new ApiException('VALIDATION_ERROR', 'Email is required.', 422, ['email' => 'Email is required.']);
        }

        $this->store->mutate(function (array $data) use ($user): array {
            $users = is_array($data['users'] ?? null) ? $data['users'] : [];
            foreach ($users as $existing) {
                if (is_array($existing) && strtolower((string) ($existing['email'] ?? '')) === strtolower((string) ($user['email'] ?? ''))) {
                    throw ApiException::conflict('A user with this email already exists.');
                }
            }
            $users[] = $user;
            $data['users'] = $users;

            return $data;
        });

        return $user;
    }

    public function touchLastLogin(string $id): void
    {
        $this->store->mutate(function (array $data) use ($id): array {
            $users = is_array($data['users'] ?? null) ? $data['users'] : [];
            foreach ($users as $index => $user) {
                if (is_array($user) && (string) ($user['id'] ?? '') === $id) {
                    $users[$index]['lastLoginAt'] = gmdate('c');
                    break;
                }
            }
            $data['users'] = $users;

            return $data;
        });
    }
}
