<?php

declare(strict_types=1);

namespace App\Repositories;

interface AssetRepositoryInterface
{
    /** @return array<string, mixed>|null */
    public function find(string $id): ?array;

    /** @param array<string, mixed> $asset */
    public function create(array $asset): array;

    /** @return array<int, array<string, mixed>> */
    public function allByOwner(string $profileId): array;
}
