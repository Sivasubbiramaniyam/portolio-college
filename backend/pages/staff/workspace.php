<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\SecurityHeaders;
use App\Core\Session;

/** @var \App\Core\Application $app */
$app = require dirname(__DIR__, 2) . '/bootstrap.php';

SecurityHeaders::send();
Session::start((array) $app->config('session', []));

require_once dirname(__DIR__, 2) . '/views/helpers.php';
require_once dirname(__DIR__, 2) . '/views/profile_view.php';

$user = $app->authService()->currentUser();
if (!is_array($user)) {
    redirect_to('/staff/login.php');
}
if ((string) ($user['role'] ?? '') === 'admin') {
    redirect_to('/admin/dashboard.php');
}
if ((string) ($user['role'] ?? '') !== 'faculty') {
    redirect_to('/');
}

$csrfToken = Csrf::token();
$flash = pull_flash();
$profileId = (string) ($user['facultyProfileId'] ?? '');
$profile = $profileId !== '' ? $app->facultyService()->maybeFind($profileId) : null;

$sidebarLinks = [
    ['key' => 'overview', 'label' => 'Dashboard', 'href' => app_url('/staff/workspace.php'), 'icon' => 'speedometer2'],
];
if ($profile !== null) {
    $sidebarLinks[] = ['key' => 'edit', 'label' => 'Edit Profile', 'href' => app_url('/staff/edit.php'), 'icon' => 'pencil-square'];
}

$pageTitle = 'Dashboard';
$pageHeading = 'Dashboard';
$activeNav = 'overview';
$useSidebar = true;
$bodyClass = 'dashboard-page';

ob_start();
?>
<?php if ($profile === null): ?>
  <div class="empty-state">
    <span class="empty-state__icon"><i class="bi bi-person-exclamation" aria-hidden="true"></i></span>
    <h3>No faculty profile is linked to this account</h3>
    <p>Your account is active, but an administrator has not linked it to a faculty profile yet. Ask an administrator to connect your account, or contact the portal maintainer.</p>
    <div class="inline-actions">
      <a class="btn btn-primary" href="<?= e(app_url('/')) ?>"><i class="bi bi-people" aria-hidden="true"></i> Browse staff list</a>
      <form method="post" action="<?= e(app_url('/logout.php')) ?>" data-confirm="Do you want to sign out?">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <button class="btn btn-ghost" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sign out</button>
      </form>
    </div>
  </div>
<?php else: ?>
  <?php
  $completion = $app->completionService()->calculate($profile);
  $actions = '<a class="btn btn-primary" href="' . e(app_url('/staff/edit.php')) . '"><i class="bi bi-pencil-square" aria-hidden="true"></i> Edit</a>';
  ?>
  <?= render_profile_hero($profile, $completion, $actions, false, true) ?>
  <?= render_profile_sections($profile) ?>
<?php endif; ?>
<?php
$content = ob_get_clean();

require dirname(__DIR__, 2) . '/views/layout.php';
