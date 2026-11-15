<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Security headers shared by HTML pages and API responses.
 */
final class SecurityHeaders
{
    public static function send(bool $isApi = false): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        if ($isApi) {
            header('Cache-Control: no-store');
            return;
        }

        header(
            "Content-Security-Policy: default-src 'self'; "
            . "script-src 'self' 'unsafe-inline'; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; "
            . "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:; "
            . "img-src 'self' data: https:; "
            . "connect-src 'self'; "
            . "frame-ancestors 'none'; base-uri 'self'; form-action 'self'"
        );
    }
}
