<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

/** @var \App\Core\Application $app */
$app = require dirname(__DIR__) . '/bootstrap.php';

/**
 * Create the MySQL/MariaDB schema from backend/db/schema.sql.
 *
 *   php backend/bin/db-migrate.php            # create database + tables
 *   php backend/bin/db-migrate.php --fresh    # drop existing tables first
 *   php backend/bin/db-migrate.php --help
 *
 * Connection settings come from the DB_* environment variables (see
 * backend/config/app.php and .env.example).
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

/** Child tables first so ON DELETE CASCADE order does not matter. */
const TABLES = [
    'assets',
    'faculty_responsibilities',
    'faculty_certifications',
    'faculty_achievements',
    'faculty_research_areas',
    'faculty_patents',
    'faculty_training_programs',
    'faculty_conferences_conducted',
    'faculty_conferences_attended',
    'faculty_projects',
    'faculty_publications',
    'faculty_experiences',
    'faculty_qualifications',
    'users',
    'faculty_profiles',
];

$options = options($argv);

if (isset($options['help'])) {
    out('UCEN Faculty Portal — MySQL schema migration');
    out('');
    out('Usage: php backend/bin/db-migrate.php [--fresh]');
    out('');
    out('  --fresh   Drop the application tables before creating them.');
    out('  --help    Show this help.');
    out('');
    out('Environment: DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_CHARSET');
    exit(0);
}

/** @var array<string, mixed> $dbConfig */
$dbConfig = (array) Config::get('database', []);
$database = (string) ($dbConfig['name'] ?? '');
if ($database === '') {
    fail('DB_DATABASE is empty; set it in the environment or backend/config/app.php.');
}
if (preg_match('/^[A-Za-z0-9_]+$/', $database) !== 1) {
    fail('DB_DATABASE may only contain letters, digits and underscores.');
}

$schemaPath = dirname(__DIR__) . '/db/schema.sql';
if (!is_file($schemaPath)) {
    fail('Schema file not found: ' . $schemaPath);
}

// 1. Ensure the database exists (connect to the server without selecting one).
try {
    $server = Database::connect($dbConfig, '');
} catch (Throwable $exception) {
    fail($exception->getMessage());
}

$server->exec(sprintf(
    'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
    $database
));
out('Database ready: ' . $database);

// 2. Optionally drop existing tables.
$db = new Database($dbConfig);

if (isset($options['fresh'])) {
    $db->pdo()->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (TABLES as $table) {
        $db->pdo()->exec('DROP TABLE IF EXISTS `' . $table . '`');
    }
    $db->pdo()->exec('SET FOREIGN_KEY_CHECKS = 1');
    out('Dropped existing tables (--fresh).');
}

// 3. Split schema.sql into statements. Strip "--" comments first (a comment may
//    contain a semicolon), then split the remainder on ";".
$raw = (string) file_get_contents($schemaPath);
$withoutComments = preg_replace('/^[ \t]*--.*$/m', '', $raw) ?? $raw;

$statements = [];
foreach (explode(';', $withoutComments) as $chunk) {
    $statement = trim($chunk);
    if ($statement !== '') {
        $statements[] = $statement;
    }
}

if ($statements === []) {
    fail('No statements found in ' . $schemaPath);
}

$applied = 0;
try {
    foreach ($statements as $statement) {
        $db->pdo()->exec($statement);
        $applied++;
    }
} catch (Throwable $exception) {
    fail('Failed applying statement ' . ($applied + 1) . ': ' . $exception->getMessage());
}

$tables = $db->select('SHOW TABLES');
out(sprintf('Applied %d statement(s). Tables now: %d.', $applied, count($tables)));
out('Next: php backend/bin/db-seed.php   (load the faculty seed profiles)');
