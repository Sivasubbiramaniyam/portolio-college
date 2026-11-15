<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Small JSON file store with shared/exclusive locking.
 *
 * This is the temporary development persistence layer. It is intentionally
 * hidden behind repository interfaces so a MySQL implementation can replace it
 * without touching the services or HTTP controllers.
 */
final class JsonStore
{
    public function __construct(
        private readonly string $path,
        private readonly ?string $seedPath = null
    ) {
    }

    public function path(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    /** @return array<string, mixed> */
    public function read(): array
    {
        $this->seedIfMissing();
        if (!is_file($this->path)) {
            return [];
        }

        $handle = @fopen($this->path, 'rb');
        if ($handle === false) {
            return [];
        }

        $contents = '';
        if (flock($handle, LOCK_SH)) {
            $contents = stream_get_contents($handle) ?: '';
            flock($handle, LOCK_UN);
        }
        fclose($handle);

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Read-modify-write under an exclusive lock.
     *
     * @param callable(array<string, mixed>): mixed $callback
     */
    public function mutate(callable $callback): mixed
    {
        $this->ensureDirectory();
        $handle = @fopen($this->path, 'c+b');
        if ($handle === false) {
            throw new RuntimeException('Unable to open the data store for writing.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock the data store.');
            }

            $contents = stream_get_contents($handle) ?: '';
            $decoded = json_decode($contents, true);
            $data = is_array($decoded) ? $decoded : [];

            $result = $callback($data);

            $encoded = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encoded === false) {
                throw new RuntimeException('Unable to encode the data store contents.');
            }

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, $encoded . PHP_EOL);
            fflush($handle);
            flock($handle, LOCK_UN);

            return $result;
        } finally {
            fclose($handle);
        }
    }

    private function seedIfMissing(): void
    {
        if (is_file($this->path) || $this->seedPath === null || !is_file($this->seedPath)) {
            return;
        }

        $this->ensureDirectory();
        @copy($this->seedPath, $this->path);
    }

    private function ensureDirectory(): void
    {
        $dir = dirname($this->path);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create the data directory.');
        }
    }
}
