<?php

declare(strict_types=1);

/**
 * Server-rendered page dispatcher.
 *
 * Maps public URLs to the PHP pages in backend/pages/:
 *
 *   /                     -> backend/pages/index.php  (plain staff listing)
 *   /staff-list.php       -> backend/pages/staff-list.php (temporary duplicate)
 *   /staff/login.php      -> backend/pages/staff/login.php
 *   /staff/register.php   -> backend/pages/staff/register.php
 *   /admin/login.php      -> backend/pages/admin/login.php
 *   /admin/register.php   -> backend/pages/admin/register.php
 *   /login.php            -> backend/pages/login.php  (redirects to staff login)
 *   /admin/dashboard.php  -> backend/pages/admin/dashboard.php
 *   /faculty/view.php     -> backend/pages/faculty/view.php
 *   /staff/workspace.php  -> backend/pages/staff/workspace.php
 *
 * The public URLs stay the same as before the reorganization; only the
 * location of the page files changed.
 */

$path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
$path = rawurldecode($path);

if ($path === '' || $path[0] !== '/') {
    $path = '/' . $path;
}

// Support deployments under a sub-path (matches app_url()/APP_BASE_PATH).
$basePath = rtrim((string) (getenv('APP_BASE_PATH') ?: ''), '/');
if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
    $path = substr($path, strlen($basePath));
    if ($path === '' || $path[0] !== '/') {
        $path = '/' . $path;
    }
}

$page = $path === '/' ? '/index.php' : rtrim($path, '/');
if ($page === '') {
    $page = '/index.php';
}

$notFound = static function (): void {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found.';
};

// Only PHP page files are routable.
if (!str_ends_with(strtolower($page), '.php')) {
    $notFound();
    return;
}

$pagesDir = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'pages');
if ($pagesDir === false) {
    $notFound();
    return;
}

$file = realpath($pagesDir . str_replace('/', DIRECTORY_SEPARATOR, $page));
$prefix = rtrim($pagesDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

if ($file === false || !is_file($file) || !str_starts_with($file, $prefix)) {
    $notFound();
    return;
}

require $file;
