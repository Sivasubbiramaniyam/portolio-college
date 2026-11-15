<?php

declare(strict_types=1);

namespace App\Repositories;

interface UserRepositoryInterface
{
    public function isConfigured(): bool;

    /** @return array<int, array<string, mixed>> */
    public function all(): array;

    /** @return array<string, mixed>|null */
    public function findById(string $id): ?array;

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array;

    /** @param array<string, mixed> $user */
    public function create(array $user): array;

    public function touchLastLogin(string $id): void;
}
