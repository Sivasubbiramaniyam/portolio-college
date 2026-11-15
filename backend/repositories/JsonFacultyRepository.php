<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\ApiException;
use App\Core\JsonStore;
use App\Core\ProfileShape;

/**
 * Temporary JSON-backed faculty repository.
 *
 * Replace this with a MySQL implementation of FacultyRepositoryInterface when
 * the database is provisioned. No service or controller needs to change.
 */
final class JsonFacultyRepository implements FacultyRepositoryInterface
{
    private JsonStore $store;

    public function __construct(string $path, ?string $seedPath = null)
    {
        $this->store = new JsonStore($path, $seedPath);
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $data = $this->store->read();
        $profiles = [];
        foreach (($data['profiles'] ?? []) as $profile) {
            if (is_array($profile)) {
                $profiles[] = ProfileShape::normalize($profile);
            }
        }

        usort($profiles, static function (array $a, array $b): int {
            $order = ((int) ($a['displayOrder'] ?? 0)) <=> ((int) ($b['displayOrder'] ?? 0));

            return $order !== 0 ? $order : strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return $profiles;
    }

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array
    {
        foreach ($this->all() as $profile) {
            if ((string) ($profile['id'] ?? '') === $id) {
                return $profile;
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    public function findDean(): ?array
    {
        foreach ($this->all() as $profile) {
            if (($profile['isDean'] ?? false) === true) {
                return $profile;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $profile */
    public function create(array $profile): array
    {
        $profile = ProfileShape::normalize($profile);

        /** @var array<string, mixed> $data */
        $data = $this->store->mutate(function (array $data) use ($profile): array {
            $profiles = is_array($data['profiles'] ?? null) ? $data['profiles'] : [];
            foreach ($profiles as $existing) {
                if (is_array($existing) && (string) ($existing['id'] ?? '') === (string) $profile['id']) {
                    throw ApiException::conflict('A profile with this identifier already exists.');
                }
            }
            $profiles[] = $profile;
            $data['profiles'] = $profiles;

            return $data;
        });

        return $this->find((string) $profile['id']) ?? $profile;
    }

    /** @param array<string, mixed> $profile */
    public function update(string $id, array $profile): array
    {
        $profile = ProfileShape::normalize($profile);
        $profile['id'] = $id;

        $this->store->mutate(function (array $data) use ($id, $profile): array {
            $profiles = is_array($data['profiles'] ?? null) ? $data['profiles'] : [];
            $found = false;
            foreach ($profiles as $index => $existing) {
                if (is_array($existing) && (string) ($existing['id'] ?? '') === $id) {
                    $profiles[$index] = $profile;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw ApiException::notFound('The faculty profile was not found.');
            }
            $data['profiles'] = $profiles;

            return $data;
        });

        return $this->find($id) ?? $profile;
    }

    public function delete(string $id): bool
    {
        $deleted = false;
        $this->store->mutate(function (array $data) use ($id, &$deleted): array {
            $profiles = is_array($data['profiles'] ?? null) ? $data['profiles'] : [];
            $remaining = [];
            foreach ($profiles as $profile) {
                if (is_array($profile) && (string) ($profile['id'] ?? '') === $id) {
                    $deleted = true;
                    continue;
                }
                $remaining[] = $profile;
            }
            $data['profiles'] = array_values($remaining);

            return $data;
        });

        return $deleted;
    }

    public function existsFacultyId(string $facultyId, ?string $exceptId = null): bool
    {
        $facultyId = strtolower(trim($facultyId));
        foreach ($this->all() as $profile) {
            if ($exceptId !== null && (string) ($profile['id'] ?? '') === $exceptId) {
                continue;
            }
            if (strtolower((string) ($profile['facultyId'] ?? '')) === $facultyId) {
                return true;
            }
        }

        return false;
    }

    public function existsEmail(string $email, ?string $exceptId = null): bool
    {
        $email = strtolower(trim($email));
        foreach ($this->all() as $profile) {
            if ($exceptId !== null && (string) ($profile['id'] ?? '') === $exceptId) {
                continue;
            }
            if (strtolower((string) ($profile['email'] ?? '')) === $email) {
                return true;
            }
        }

        return false;
    }
}
