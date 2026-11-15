<?php

declare(strict_types=1);

use App\Core\SecurityHeaders;
use App\Core\Session;

/**
 * Legacy sign-in URL. Sign-in is now split by role:
 *   staff -> /staff/login.php
 *   admin -> /admin/login.php
 *
 * This page keeps old links and bookmarks working by forwarding to the staff
 * sign-in, preserving the "signed out" notice when present.
 *
 * @var \App\Core\Application $app
 */
$app = require __DIR__ . '/../bootstrap.php';

SecurityHeaders::send();
Session::start((array) $app->config('session', []));

require_once __DIR__ . '/../views/helpers.php';

$target = '/staff/login.php';
if (isset($_GET['signedout'])) {
    $target .= '?signedout=1';
}

redirect_to($target);
