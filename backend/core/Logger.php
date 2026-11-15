<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Structured JSON-lines logger. Secrets are redacted before anything is
 * written, and log records never include request bodies.
 */
final class Logger
{
    private static ?string $path = null;

    public static function init(string $path): void
    {
        self::$path = $path;
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    /** @param array<string, mixed> $context */
    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public static function write(string $level, string $message, array $context = []): void
    {
        if (self::$path === null) {
            return;
        }

        $record = [
            'timestamp' => gmdate('c'),
            'level' => $level,
            'message' => $message,
            'context' => self::redact($context),
        ];

        $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            return;
        }

        $handle = @fopen(self::$path, 'ab');
        if ($handle === false) {
            return;
        }
        if (flock($handle, LOCK_EX)) {
            fwrite($handle, $line . PHP_EOL);
            fflush($handle);
            flock($handle, LOCK_UN);
        }
        fclose($handle);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private static function redact(array $context): array
    {
        $redacted = [];
        foreach ($context as $key => $value) {
            $lower = strtolower((string) $key);
            if (preg_match('/password|passwd|secret|token|authorization|cookie|hash/', $lower)) {
                $redacted[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $redacted[$key] = self::redact($value);
            } elseif (is_string($value) && strlen($value) > 500) {
                $redacted[$key] = substr($value, 0, 500) . '…';
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }
}
