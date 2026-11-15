<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\ApiException;
use App\Core\Database;
use PDOException;

/**
 * MySQL upload metadata repository. Files themselves stay on disk outside the
 * web root; only metadata is stored here, exactly as the JSON provider does.
 */
final class MysqlAssetRepository implements AssetRepositoryInterface
{
    use TranslatesDriverErrors;

    /** API field => database column. */
    private const FIELDS = [
        'id' => 'id',
        'ownerProfileId' => 'owner_profile_id',
        'storageKey' => 'storage_key',
        'url' => 'url',
        'mimeType' => 'mime_type',
        'byteSize' => 'byte_size',
        'originalName' => 'original_name',
        'kind' => 'kind',
        'uploadedBy' => 'uploaded_by',
        'createdAt' => 'created_at',
    ];

    public function __construct(private readonly Database $db)
    {
    }

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array
    {
        $row = $this->db->selectOne('SELECT * FROM assets WHERE id = ? LIMIT 1', [$id]);

        return $row === null ? null : $this->hydrate($row);
    }

    /** @param array<string, mixed> $asset */
    public function create(array $asset): array
    {
        $values = $this->values($asset);
        $sql = 'INSERT INTO assets (' . implode(', ', array_keys($values)) . ')'
            . ' VALUES (' . Database::placeholders($values) . ')';

        try {
            $this->db->execute($sql, array_values($values));
        } catch (PDOException $exception) {
            if (self::isDuplicateKey($exception)) {
                throw ApiException::conflict('An asset with this identifier already exists.');
            }
            throw $exception;
        }

        return $this->find((string) $values['id']) ?? $asset;
    }

    /** @return array<int, array<string, mixed>> */
    public function allByOwner(string $profileId): array
    {
        $rows = $this->db->select(
            'SELECT * FROM assets WHERE owner_profile_id = ? ORDER BY created_at ASC, id ASC',
            [$profileId]
        );

        return array_map(fn (array $row): array => $this->hydrate($row), $rows);
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    private function hydrate(array $row): array
    {
        $asset = [];
        foreach (self::FIELDS as $api => $column) {
            $value = $row[$column] ?? null;
            if ($api === 'byteSize') {
                $asset[$api] = (int) $value;
            } elseif ($api === 'ownerProfileId') {
                $asset[$api] = ($value === null || $value === '') ? null : (string) $value;
            } else {
                $asset[$api] = $value === null ? '' : (string) $value;
            }
        }

        return $asset;
    }

    /** @param array<string, mixed> $asset
     *  @return array<string, mixed>
     */
    private function values(array $asset): array
    {
        $values = [];
        foreach (self::FIELDS as $api => $column) {
            $value = $asset[$api] ?? null;

            if ($api === 'byteSize') {
                $values[$column] = (int) $value;
                continue;
            }
            if ($api === 'ownerProfileId') {
                $text = trim((string) ($value ?? ''));
                $values[$column] = $text === '' ? null : $text;
                continue;
            }
            if ($api === 'createdAt' && ($value === null || $value === '')) {
                $values[$column] = gmdate('c');
                continue;
            }

            $values[$column] = (string) ($value ?? '');
        }

        return $values;
    }
}
