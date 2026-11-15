<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\ApiException;
use App\Core\JsonStore;

final class JsonAssetRepository implements AssetRepositoryInterface
{
    private JsonStore $store;

    public function __construct(string $path)
    {
        $this->store = new JsonStore($path);
    }

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array
    {
        $data = $this->store->read();
        foreach (($data['assets'] ?? []) as $asset) {
            if (is_array($asset) && (string) ($asset['id'] ?? '') === $id) {
                return $asset;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $asset */
    public function create(array $asset): array
    {
        $this->store->mutate(function (array $data) use ($asset): array {
            $assets = is_array($data['assets'] ?? null) ? $data['assets'] : [];
            foreach ($assets as $existing) {
                if (is_array($existing) && (string) ($existing['id'] ?? '') === (string) ($asset['id'] ?? '')) {
                    throw ApiException::conflict('An asset with this identifier already exists.');
                }
            }
            $assets[] = $asset;
            $data['assets'] = $assets;

            return $data;
        });

        return $asset;
    }

    /** @return array<int, array<string, mixed>> */
    public function allByOwner(string $profileId): array
    {
        $data = $this->store->read();
        $assets = [];
        foreach (($data['assets'] ?? []) as $asset) {
            if (is_array($asset) && (string) ($asset['ownerProfileId'] ?? '') === $profileId) {
                $assets[] = $asset;
            }
        }

        return $assets;
    }
}
