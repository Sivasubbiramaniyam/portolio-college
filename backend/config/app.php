<?php

declare(strict_types=1);

/**
 * Application configuration.
 *
 * The paths are resolved from APP_BASE_PATH, which bootstrap.php defines as the
 * repository root. Override deployment-specific values with environment
 * variables; never commit real credentials here.
 */

$uploadsPath = APP_BASE_PATH . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads';
$storagePath = APP_BASE_PATH . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'storage';
$logsPath = APP_BASE_PATH . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'logs';

return [
    'name' => 'UCEN Faculty Portal',
    'version' => '1.0.0',
    'env' => getenv('APP_ENV') ?: 'development',
    'debug' => filter_var(getenv('APP_DEBUG') ?: 'true', FILTER_VALIDATE_BOOLEAN),
    'timezone' => 'Asia/Kolkata',
    'base_path' => rtrim((string) (getenv('APP_BASE_PATH') ?: ''), '/'),

    'session' => [
        'name' => 'ucen_portal_session',
        'lifetime' => 60 * 60 * 2,
        'cookie_path' => '/',
        'cookie_samesite' => 'Lax',
        'cookie_secure' => filter_var(getenv('SESSION_COOKIE_SECURE') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        'use_strict_mode' => true,
    ],

    'security' => [
        'csrf_header' => 'HTTP_X_CSRF_TOKEN',
        'csrf_field' => '_csrf',
        'max_login_attempts' => 8,
        'login_window_seconds' => 300,
    ],

    'paths' => [
        'storage' => $storagePath,
        'uploads' => $uploadsPath,
        'logs' => $logsPath,
        'faculty_file' => $storagePath . DIRECTORY_SEPARATOR . 'faculty.json',
        'faculty_seed' => $storagePath . DIRECTORY_SEPARATOR . 'seed' . DIRECTORY_SEPARATOR . 'faculty.json',
        'users_file' => $storagePath . DIRECTORY_SEPARATOR . 'users.json',
        'whitelist_file' => $storagePath . DIRECTORY_SEPARATOR . 'whitelist.json',
        'assets_file' => $storagePath . DIRECTORY_SEPARATOR . 'assets.json',
    ],

    'database' => [
        // MySQL / MariaDB connection used by the "mysql" repositories.
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'name' => getenv('DB_DATABASE') ?: 'ucen_portal',
        'user' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    ],

    'repository' => [
        // "mysql" is the production provider; set DATA_DRIVER=json to fall back
        // to the temporary JSON files without touching the API/service layer.
        'driver' => getenv('DATA_DRIVER') ?: 'mysql',
    ],

    'auth' => [
        // Authentication users are created with backend/bin/make-user.php.
        // No credentials or password hashes are shipped with the application.
        'user_provider' => getenv('AUTH_USER_PROVIDER') ?: 'mysql',
        'session_key' => 'auth_user_id',
    ],

    'uploads' => [
        'photo_max_bytes' => 5 * 1024 * 1024,
        'banner_max_bytes' => 5 * 1024 * 1024,
        'attachment_max_bytes' => 3 * 1024 * 1024,
        'photo_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'attachment_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'],
    ],

    'institution' => [
        'name' => 'University College of Engineering Nagercoil',
        'tamilName' => 'பல்கலைக்கழக பொறியியல் கல்லூரி நாகர்கோவில்',
        'affiliation' => 'A Constituent College of Anna University, Chennai',
        'address' => 'Industrial Estate, Konam, Nagercoil, Kanyakumari District, Tamil Nadu – 629 004',
        'doteCode' => '4999',
        'primaryColour' => '#7B5EF0',
        'logoUrl' => '/frontend/assets/anna-university-logo.svg',
        'defaultProfileUrl' => '/frontend/assets/default-profile.svg',
        'deanId' => 'fac-dean',
        'deanEmail' => 'dean@ucen.ac.in',
    ],
];
