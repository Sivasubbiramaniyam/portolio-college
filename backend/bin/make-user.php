<?php

declare(strict_types=1);

use App\Core\ApiException;
use App\Core\Enums;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

/** @var \App\Core\Application $app */
$app = require dirname(__DIR__) . '/bootstrap.php';

/**
 * Create and list authentication users for the temporary JSON user provider.
 *
 * Passwords are hashed with Argon2id where available and are never written in
 * plaintext. The users file is intentionally not committed to version control.
 */

/** @return array<string, string> */
function cli_options(array $argv): array
{
    $options = [];
    foreach (array_slice($argv, 1) as $argument) {
        if (!str_starts_with($argument, '--')) {
            continue;
        }
        $argument = substr($argument, 2);
        $parts = explode('=', $argument, 2);
        $options[$parts[0]] = $parts[1] ?? '1';
    }

    return $options;
}

function out(string $message = ''): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function fail(string $message, int $code = 1): never
{
    fwrite(STDERR, 'Error: ' . $message . PHP_EOL);
    exit($code);
}

function prompt(string $label, string $default = ''): string
{
    $suffix = $default !== '' ? ' [' . $default . ']' : '';
    fwrite(STDOUT, $label . $suffix . ': ');
    $line = fgets(STDIN);
    if ($line === false) {
        fail('No input received.');
    }
    $value = trim($line);

    return $value !== '' ? $value : $default;
}

function prompt_secret(string $label): string
{
    fwrite(STDOUT, $label . ': ');
    $hidden = false;
    if (DIRECTORY_SEPARATOR === '/') {
        @shell_exec('stty -echo 2>/dev/null');
        $hidden = true;
    }
    $line = fgets(STDIN);
    if ($hidden) {
        @shell_exec('stty echo 2>/dev/null');
        fwrite(STDOUT, PHP_EOL);
    }
    if ($line === false) {
        fail('No input received.');
    }

    return rtrim($line, "\r\n");
}

function print_help(): void
{
    out('UCEN Faculty Portal — create an authentication user');
    out('');
    out('Usage:');
    out('  php backend/bin/make-user.php --email=you@ucen.ac.in --role=admin');
    out('  php backend/bin/make-user.php --email=you@ucen.ac.in --role=faculty --faculty=fac-ramanathan');
    out('  php backend/bin/make-user.php --list');
    out('');
    out('Options:');
    out('  --email=EMAIL         Login email address');
    out('  --role=ROLE           admin, faculty or viewer (default: faculty)');
    out('  --faculty=PROFILE_ID  Required for faculty accounts; optional for admin');
    out('  --name=NAME           Display name stored with the account');
    out('  --password-stdin      Read the password from the first line of STDIN');
    out('  --password=PASSWORD   Supply a password (avoid: shell history)');
    out('  --list                List configured users without hashes');
    out('  --help                Show this help');
    out('');
    out('Passwords must be at least 10 characters. Users are stored in backend/storage/users.json,');
    out('which is ignored by Git. Replace the JSON user provider with MySQL later without changing');
    out('the HTTP layer.');
}

$options = cli_options($argv);

if (isset($options['help'])) {
    print_help();
    exit(0);
}

$users = $app->userRepository();

if (isset($options['list'])) {
    $all = $users->all();
    if ($all === []) {
        out('No users are configured. Create one with:');
        out('  php backend/bin/make-user.php --email=you@ucen.ac.in --role=admin');
        exit(0);
    }
    out(sprintf('%-32s %-9s %-18s %-8s %s', 'Email', 'Role', 'Faculty profile', 'Active', 'Last login'));
    foreach ($all as $user) {
        out(sprintf(
            '%-32s %-9s %-18s %-8s %s',
            (string) ($user['email'] ?? ''),
            (string) ($user['role'] ?? ''),
            (string) (($user['facultyProfileId'] ?? '') ?: '—'),
            (($user['isActive'] ?? true) === true ? 'yes' : 'no'),
            (string) (($user['lastLoginAt'] ?? '') ?: 'never')
        ));
    }
    exit(0);
}

$email = trim((string) ($options['email'] ?? ''));
if ($email === '') {
    $email = prompt('Email address');
}
if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fail('Enter a valid email address.');
}

$role = trim((string) ($options['role'] ?? ''));
if ($role === '') {
    $role = prompt('Role (admin/faculty/viewer)', 'faculty');
}
if (!in_array($role, Enums::ROLES, true)) {
    fail('Role must be one of: ' . implode(', ', Enums::ROLES) . '.');
}

$existing = $users->findByEmail($email);
if ($existing !== null) {
    fail('A user with that email already exists.');
}

$facultyProfiles = $app->facultyService()->all();
$facultyProfileId = trim((string) ($options['faculty'] ?? ''));
if ($facultyProfileId === '' && $role === 'faculty') {
    out('Published faculty profiles:');
    foreach ($facultyProfiles as $profile) {
        out('  ' . $profile['id'] . '  ' . $profile['name'] . '  (' . ($profile['department'] ?: 'no department') . ')');
    }
    $facultyProfileId = prompt('Faculty profile ID');
}
if ($facultyProfileId !== '') {
    $linked = $app->facultyService()->maybeFind($facultyProfileId);
    if ($linked === null) {
        fail('No faculty profile exists with id "' . $facultyProfileId . '".');
    }
    foreach ($users->all() as $user) {
        if ((string) ($user['facultyProfileId'] ?? '') === $facultyProfileId) {
            fail('That faculty profile is already linked to ' . (string) ($user['email'] ?? 'another user') . '.');
        }
    }
}

$password = '';
if (isset($options['password-stdin'])) {
    $line = fgets(STDIN);
    if ($line === false) {
        fail('No password was received on standard input.');
    }
    $password = rtrim($line, "\r\n");
} elseif (isset($options['password'])) {
    $password = (string) $options['password'];
    fwrite(STDERR, 'Warning: passwords passed on the command line may remain in shell history.' . PHP_EOL);
} else {
    $password = prompt_secret('Password (minimum 10 characters)');
    $confirm = prompt_secret('Confirm password');
    if (!hash_equals($password, $confirm)) {
        fail('The passwords do not match.');
    }
}

if (strlen($password) < 10) {
    fail('The password must be at least 10 characters long.');
}
if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    fail('The password must include at least one letter and one number.');
}

$algorithm = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
$hash = password_hash($password, $algorithm);
if (!is_string($hash) || $hash === '') {
    fail('The password could not be hashed.');
}

$name = trim((string) ($options['name'] ?? ''));
$user = [
    'id' => 'usr-' . bin2hex(random_bytes(6)),
    'email' => strtolower($email),
    'name' => $name,
    'role' => $role,
    'facultyProfileId' => $facultyProfileId !== '' ? $facultyProfileId : null,
    'passwordHash' => $hash,
    'isActive' => true,
    'createdAt' => gmdate('c'),
    'lastLoginAt' => null,
];

try {
    $users->create($user);
} catch (ApiException $exception) {
    fail($exception->getMessage());
} catch (Throwable $exception) {
    fail('Unable to write the user store: ' . $exception->getMessage());
}

out('');
out('User created successfully.');
out('  Email:           ' . $user['email']);
out('  Role:            ' . $user['role']);
out('  Faculty profile: ' . ($user['facultyProfileId'] ?? '—'));
out('  Password hash:   ' . (str_starts_with($hash, '$argon2id$') ? 'Argon2id' : 'bcrypt'));
out('');
out('Sign in at /staff/login.php (administrators: /admin/login.php). Review the manual testing steps in docs/SETUP.md.');
