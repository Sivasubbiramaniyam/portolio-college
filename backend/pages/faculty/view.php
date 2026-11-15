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
$csrfToken = Csrf::token();
$flash = pull_flash();

$id = trim((string) ($_GET['id'] ?? ''));
$profile = $id !== '' ? $app->facultyService()->maybeFind($id) : null;

$pageTitle = 'Faculty profile';
$pageHeading = 'Faculty profile';
$bodyClass = 'profile-view-page';
$activeNav = '';
$useSidebar = true;
$sidebarPinned = true;
$sidebarBack = ['href' => app_url('/'), 'label' => 'Staff list'];
$sidebarTitle = 'On this page';
$sidebarLinks = [];
foreach (profile_section_index() as $section) {
    $sidebarLinks[] = [
        'key' => 'section-' . $section['id'],
        'label' => $section['label'],
        'href' => '#' . $section['id'],
        'icon' => $section['icon'],
    ];
}
$sidebarAccountLinks = [];
if (is_array($user)) {
    if ((string) ($user['role'] ?? '') === 'admin') {
        $sidebarAccountLinks[] = ['label' => 'Admin Dashboard', 'href' => app_url('/admin/dashboard.php'), 'icon' => 'sliders2'];
    } elseif ((string) ($user['role'] ?? '') === 'faculty') {
        $sidebarAccountLinks[] = ['label' => 'My Workspace', 'href' => app_url('/staff/workspace.php'), 'icon' => 'person-circle'];
    }
}

if ($profile === null) {
    http_response_code(404);
    ob_start();
    ?>
    <div class="empty-state">
      <span class="empty-state__icon"><i class="bi bi-person-x" aria-hidden="true"></i></span>
      <h3>Profile not found</h3>
      <p>The requested faculty profile does not exist or may have been removed.</p>
      <a class="btn btn-primary" href="<?= e(app_url('/')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to staff list</a>
    </div>
    <?php
    $content = ob_get_clean();
    require dirname(__DIR__, 2) . '/views/layout.php';
    exit;
}

$pageTitle = (string) ($profile['name'] ?? 'Faculty profile');
$pageHeading = (string) ($profile['name'] ?? 'Faculty profile');
$completion = $app->completionService()->calculate($profile);
$canEdit = is_array($user) && (
    (string) ($user['role'] ?? '') === 'admin'
    || ((string) ($user['role'] ?? '') === 'faculty' && (string) ($user['facultyProfileId'] ?? '') === (string) $profile['id'])
);
$editUrl = is_array($user) && (string) ($user['role'] ?? '') === 'admin'
    ? app_url('/admin/edit.php?id=' . rawurlencode((string) $profile['id']))
    : app_url('/staff/edit.php');

$actions = '';
if ($canEdit) {
    $actions = '<a class="btn btn-primary" href="' . e($editUrl) . '"><i class="bi bi-pencil-square" aria-hidden="true"></i> Edit profile</a>';
}

ob_start();
?>
<?= render_profile_hero($profile, $completion, $actions, $canEdit) ?>

<?= render_profile_sections($profile) ?>
<?php
$content = ob_get_clean();

require dirname(__DIR__, 2) . '/views/layout.php';
