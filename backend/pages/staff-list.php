<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\SecurityHeaders;
use App\Core\Session;

/**
 * Temporary plain-HTML staff listing.
 *
 * This page is a deliberate duplicate of the entrance listing so it can be
 * reviewed and removed later without touching the rest of the frontend.
 *
 * @var \App\Core\Application $app
 */
$app = require __DIR__ . '/../bootstrap.php';

SecurityHeaders::send();
Session::start((array) $app->config('session', []));

require_once __DIR__ . '/../views/helpers.php';
require_once __DIR__ . '/../views/staff_list.php';

$user = $app->authService()->currentUser();
$csrfToken = Csrf::token();

$result = $app->facultyService()->list([
    'includeDean' => '1',
    'pageSize' => 200,
]);
$profiles = $result['items'];
usort($profiles, static function (array $a, array $b): int {
    return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
});

echo render_staff_list_document($profiles, [
    'user' => $user,
    'csrfToken' => $csrfToken,
    'heading' => 'Faculty staff (draft listing)',
]);
