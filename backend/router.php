<?php

declare(strict_types=1);

/**
 * Router for PHP's built-in development server.
 *
 * Start the application from the repository root:
 *
 *     php -S localhost:8000 backend/router.php
 *
 * - Real static files (frontend CSS/JS/images, fonts, PDFs) are served by the
 *   built-in server.
 * - Every other request is forwarded to the front controller, which handles
 *   both the JSON API (/api/v1/*) and the server-rendered pages.
 * - Private application directories and sensitive files are never served.
 */

$root = dirname(__DIR__);
$uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');
$path = rawurldecode($path);

if ($path === '' || $path[0] !== '/') {
    $path = '/' . $path;
}

// Private paths: application code, documentation, runtime storage, VCS data.
$private = [
    '#^/backend(/|$)#i',
    '#^/docs(/|$)#i',
    '#^/storage(/|$)#i',
    '#^/node_modules(/|$)#i',
    '#^/(\.git|\.idea|\.vscode)(/|$)#i',
    '#(^|/)\.#',
];

foreach ($private as $pattern) {
    if (preg_match($pattern, $path) === 1) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Not found.';
        return true;
    }
}

// Explicitly deny server-side configuration and package files.
$basename = strtolower(basename($path));
$blockedFiles = [
    'router.php',
    'server.js',
    'package.json',
    'package-lock.json',
    'mcp.json',
    'readme.md',
    '.htaccess',
    '.gitignore',
];
if (in_array($basename, $blockedFiles, true)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found.';
    return true;
}

$target = realpath($root . str_replace('/', DIRECTORY_SEPARATOR, $path));

if ($target !== false && is_file($target)) {
    $rootPrefix = rtrim((string) realpath($root), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (!str_starts_with($target, $rootPrefix)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Not found.';
        return true;
    }

    $extension = strtolower(pathinfo($target, PATHINFO_EXTENSION));
    if ($extension !== 'php') {
        $allowed = ['css', 'js', 'mjs', 'svg', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'ico', 'woff', 'woff2', 'ttf', 'otf', 'pdf', 'txt', 'html', 'map', 'json'];
        if (in_array($extension, $allowed, true)) {
            return false;
        }
    }
}

// Let the built-in server resolve a directory index (for example /frontend/).
if ($target !== false && is_dir($target) && is_file($target . DIRECTORY_SEPARATOR . 'index.html')) {
    return false;
}

// Everything else (/, /staff/login.php, /admin/login.php, /admin/dashboard.php,
// /api/v1/*, ...) is handled by the single front controller.
require $root . '/backend/public/index.php';
return true;
