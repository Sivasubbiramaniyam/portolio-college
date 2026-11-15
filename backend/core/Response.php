<?php

declare(strict_types=1);

namespace App\Core;

/**
 * JSON response helpers. Every API response uses the documented envelope.
 */
final class Response
{
    /** @param array<string, mixed> $meta */
    public static function json(mixed $data, int $status = 200, array $meta = []): never
    {
        self::headers();
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        $payload = [
            'data' => $data,
            'meta' => (object) $meta,
        ];

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /** @param array<string, string> $fields */
    public static function error(string $code, string $message, array $fields = [], int $status = 400): never
    {
        self::headers();
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        $payload = [
            'error' => [
                'code' => $code,
                'message' => $message,
                'fields' => (object) $fields,
            ],
        ];

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public static function noContent(): never
    {
        self::headers();
        http_response_code(204);
        exit;
    }

    private static function headers(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Cache-Control: no-store');
    }
}
