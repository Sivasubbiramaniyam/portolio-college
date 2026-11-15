<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Config;
use App\Core\Logger;

if (!defined('APP_BASE_PATH')) {
    define('APP_BASE_PATH', dirname(__DIR__));
}

if (!defined('APP_STARTED_AT')) {
    define('APP_STARTED_AT', microtime(true));
}

/**
 * Explicit PSR-4-style autoloader. Keeping it explicit avoids requiring
 * Composer for this plain-PHP deployment.
 */
spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'App\\Core\\' => APP_BASE_PATH . '/backend/core/',
        'App\\Repositories\\' => APP_BASE_PATH . '/backend/repositories/',
        'App\\Services\\' => APP_BASE_PATH . '/backend/services/',
        'App\\Middleware\\' => APP_BASE_PATH . '/backend/middleware/',
        'App\\Api\\V1\\' => APP_BASE_PATH . '/backend/api/v1/',
    ];

    foreach ($prefixes as $prefix => $directory) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $file = $directory . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});

require_once APP_BASE_PATH . '/backend/core/helpers.php';

$config = require APP_BASE_PATH . '/backend/config/app.php';
Config::init($config);

date_default_timezone_set((string) ($config['timezone'] ?? 'Asia/Kolkata'));

// Never expose stack traces to browsers. Details go to the structured log.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

foreach (['paths.storage', 'paths.uploads', 'paths.logs'] as $pathKey) {
    $path = (string) Config::get($pathKey, '');
    if ($path !== '' && !is_dir($path)) {
        @mkdir($path, 0775, true);
    }
}

Logger::init((string) ($config['paths']['logs'] ?? APP_BASE_PATH . '/backend/logs') . DIRECTORY_SEPARATOR . 'app.log');

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    Logger::warning('PHP runtime warning', [
        'severity' => $severity,
        'message' => $message,
        'file' => $file,
        'line' => $line,
    ]);

    return true;
});

return new Application($config);
