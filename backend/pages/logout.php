<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\SecurityHeaders;
use App\Core\Session;

/** @var \App\Core\Application $app */
$app = require __DIR__ . '/../bootstrap.php';

SecurityHeaders::send();
Session::start((array) $app->config('session', []));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('/');
}

if (!Csrf::check(is_string($_POST['_csrf'] ?? null) ? (string) $_POST['_csrf'] : null)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'The sign-out request could not be verified. Please refresh the page and try again.';
    exit;
}

$user = $app->authService()->currentUser();
$wasAdmin = is_array($user) && (string) ($user['role'] ?? '') === 'admin';

$app->authService()->logout();

redirect_to(($wasAdmin ? '/admin/login.php' : '/staff/login.php') . '?signedout=1');
