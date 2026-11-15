<?php

declare(strict_types=1);

use App\Core\Logger;

/** @var \App\Core\Application $app */
$app = require __DIR__ . '/../bootstrap.php';

header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: strict-origin-when-cross-origin');

$id = trim((string) ($_GET['id'] ?? ''));
if (!preg_match('/^asset_[a-f0-9]{24}$/', $id)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File not found.';
    exit;
}

$asset = $app->assetRepository()->find($id);
if ($asset === null) {
    Logger::warning('Media asset not found', ['assetId' => $id]);
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File not found.';
    exit;
}

$storageKey = str_replace('\\', '/', (string) ($asset['storageKey'] ?? ''));
if (!preg_match('#^\d{4}/\d{2}/[a-f0-9]{32}\.(?:jpg|png|webp|gif|pdf)$#', $storageKey)) {
    Logger::warning('Rejected unsafe media storage key', ['assetId' => $id]);
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File not found.';
    exit;
}

$uploadsRoot = realpath((string) $app->config('paths.uploads', ''));
if ($uploadsRoot === false) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File not found.';
    exit;
}

$absolute = realpath($uploadsRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storageKey));
if ($absolute === false || !is_file($absolute)) {
    Logger::warning('Media file missing from disk', ['assetId' => $id]);
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File not found.';
    exit;
}

$rootPrefix = rtrim($uploadsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
if (!str_starts_with($absolute, $rootPrefix)) {
    Logger::error('Blocked media path traversal attempt', ['assetId' => $id]);
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File not found.';
    exit;
}

$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
$mime = '';
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo !== false) {
        $detected = finfo_file($finfo, $absolute);
        finfo_close($finfo);
        if (is_string($detected)) {
            $mime = strtolower($detected);
        }
    }
}
if ($mime === '' && function_exists('mime_content_type')) {
    $detected = mime_content_type($absolute);
    if (is_string($detected)) {
        $mime = strtolower($detected);
    }
}
if (!in_array($mime, $allowed, true)) {
    Logger::warning('Blocked media with unexpected MIME type', ['assetId' => $id, 'mimeType' => $mime]);
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'File not found.';
    exit;
}

$size = filesize($absolute);
$modified = filemtime($absolute);
$etag = '"' . md5($id . ':' . $modified . ':' . $size) . '"';
$ifNoneMatch = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
if ($ifNoneMatch !== '' && hash_equals($etag, $ifNoneMatch)) {
    http_response_code(304);
    header('ETag: ' . $etag);
    header('Cache-Control: private, max-age=86400');
    exit;
}

$originalName = (string) ($asset['originalName'] ?? 'file');
$originalName = preg_replace('/[^A-Za-z0-9._ -]+/', '_', basename($originalName)) ?: 'file';

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) $size);
header('Content-Disposition: inline; filename="' . $originalName . '"');
header('Cache-Control: private, max-age=86400');
header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $modified) . ' GMT');

while (ob_get_level() > 0) {
    ob_end_clean();
}
readfile($absolute);
exit;
