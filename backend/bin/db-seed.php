<?php

declare(strict_types=1);

use App\Core\ProfileShape;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

/** @var \App\Core\Application $app */
$app = require dirname(__DIR__) . '/bootstrap.php';

/**
 * Load faculty profiles into the MySQL database.
 *
 *   php backend/bin/db-seed.php                    # import seed/faculty.json
 *   php backend/bin/db-seed.php --from=path.json   # import another file
 *   php backend/bin/db-seed.php --help
 *
 * Source resolution order: --from, backend/storage/faculty.json (existing
 * runtime data), then backend/storage/seed/faculty.json. Existing profiles are
 * updated, new profiles are inserted, so the command is safe to re-run.
 */

function out(string $message = ''): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function fail(string $message): never
{
    fwrite(STDERR, 'Error: ' . $message . PHP_EOL);
    exit(1);
}

/** @return array<string, string> */
function options(array $argv): array
{
    $parsed = [];
    foreach (array_slice($argv, 1) as $argument) {
        if (!str_starts_with($argument, '--')) {
            continue;
        }
        $parts = explode('=', substr($argument, 2), 2);
        $parsed[$parts[0]] = $parts[1] ?? '1';
    }

    return $parsed;
}

$options = options($argv);

if (isset($options['help'])) {
    out('UCEN Faculty Portal — seed faculty profiles into MySQL');
    out('');
    out('Usage: php backend/bin/db-seed.php [--from=PATH]');
    out('');
    out('  --from=PATH   Import a specific JSON file of {"profiles": [...]}.');
    out('  --help        Show this help.');
    out('');
    out('Run php backend/bin/db-migrate.php first to create the schema.');
    exit(0);
}

$basePath = defined('APP_BASE_PATH') ? APP_BASE_PATH : dirname(__DIR__, 2);
$runtimePath = $basePath . '/backend/storage/faculty.json';
$seedPath = $basePath . '/backend/storage/seed/faculty.json';

$source = (string) ($options['from'] ?? '');
if ($source === '') {
    $source = is_file($runtimePath) ? $runtimePath : $seedPath;
}
if (!is_file($source)) {
    fail('Source file not found: ' . $source);
}

$decoded = json_decode((string) file_get_contents($source), true);
if (!is_array($decoded) || !isset($decoded['profiles']) || !is_array($decoded['profiles'])) {
    fail('Source file must contain a JSON object with a "profiles" array.');
}

$repository = $app->facultyRepository();

$created = 0;
$updated = 0;
$skipped = 0;

foreach ($decoded['profiles'] as $raw) {
    if (!is_array($raw)) {
        $skipped++;
        continue;
    }

    $profile = ProfileShape::normalize($raw);
    $id = (string) $profile['id'];
    if ($id === '') {
        $skipped++;
        continue;
    }

    try {
        if ($repository->find($id) === null) {
            $repository->create($profile);
            $created++;
        } else {
            $repository->update($id, $profile);
            $updated++;
        }
    } catch (Throwable $exception) {
        fwrite(STDERR, '  ! ' . $id . ': ' . $exception->getMessage() . PHP_EOL);
        $skipped++;
    }
}

$total = count($repository->all());
out('');
out(sprintf('Seeded from %s', $source));
out(sprintf('  created: %d, updated: %d, skipped: %d', $created, $updated, $skipped));
out(sprintf('  faculty profiles in database: %d', $total));
