<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session bootstrap and helpers.
 */
final class Session
{
    private static bool $started = false;

    /** @param array<string, mixed> $config */
    public static function start(array $config): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $lifetime = (int) ($config['lifetime'] ?? 7200);
        $secure = (bool) ($config['cookie_secure'] ?? false) || self::isHttps();

        ini_set('session.use_strict_mode', ($config['use_strict_mode'] ?? true) ? '1' : '0');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.gc_maxlifetime', (string) $lifetime);
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');

        session_name((string) ($config['name'] ?? 'ucen_portal_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => (string) ($config['cookie_path'] ?? '/'),
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => (string) ($config['cookie_samesite'] ?? 'Lax'),
        ]);

        session_start();
        self::$started = true;

        if (!isset($_SESSION['started_at'])) {
            $_SESSION['started_at'] = time();
        }
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
        }
    }

    public static function isStarted(): bool
    {
        return self::$started || session_status() === PHP_SESSION_ACTIVE;
    }

    public static function id(): string
    {
        return session_id();
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'] ?? '/',
                    'domain' => $params['domain'] ?? '',
                    'secure' => (bool) ($params['secure'] ?? false),
                    'httponly' => true,
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }
        session_destroy();
        self::$started = false;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = self::get($key, $default);
        self::forget($key);

        return $value;
    }

    public static function touch(): void
    {
        $_SESSION['last_activity'] = time();
    }

    public static function expired(int $lifetime): bool
    {
        $last = (int) ($_SESSION['last_activity'] ?? 0);

        return $last > 0 && (time() - $last) > $lifetime;
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function flashPull(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
            return true;
        }

        return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }
}
