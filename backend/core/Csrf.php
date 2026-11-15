<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF token generation and validation.
 */
final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set(self::KEY, $token);
        }

        return $token;
    }

    public static function rotate(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set(self::KEY, $token);

        return $token;
    }

    public static function check(?string $token): bool
    {
        $expected = Session::get(self::KEY);
        if (!is_string($expected) || $expected === '' || !is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }

    public static function validate(Request $request, ?string $headerName = null): void
    {
        if ($request->isReadOnly()) {
            return;
        }

        $headerName ??= (string) Config::get('security.csrf_header', 'HTTP_X_CSRF_TOKEN');
        // Request stores lower-case header names, so normalise the configured
        // $_SERVER key (HTTP_X_CSRF_TOKEN) to x-csrf-token.
        $normalised = strtolower(str_replace('_', '-', preg_replace('/^HTTP_/', '', $headerName) ?? $headerName));
        $token = $request->header($normalised);
        if ($token === null || $token === '') {
            $token = $request->input((string) Config::get('security.csrf_field', '_csrf'));
        }

        if (!self::check(is_string($token) ? $token : null)) {
            throw ApiException::csrf();
        }
    }
}
