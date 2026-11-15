<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Profile persistence boundary.
 *
 * The temporary JSON implementation and any future MySQL implementation must
 * both honour this contract.
 */
interface FacultyRepositoryInterface
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array;

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array;

    /** @return array<string, mixed>|null */
    public function findDean(): ?array;

    /** @param array<string, mixed> $profile */
    public function create(array $profile): array;

    /** @param array<string, mixed> $profile */
    public function update(string $id, array $profile): array;

    public function delete(string $id): bool;

    public function existsFacultyId(string $facultyId, ?string $exceptId = null): bool;

    public function existsEmail(string $email, ?string $exceptId = null): bool;
}
