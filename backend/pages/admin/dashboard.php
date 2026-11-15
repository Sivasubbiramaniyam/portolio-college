<?php

declare(strict_types=1);

use App\Core\ApiException;
use App\Core\Csrf;
use App\Core\Enums;
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
    redirect_to('/admin/login.php');
}
if ((string) ($user['role'] ?? '') === 'faculty') {
    redirect_to('/staff/workspace.php');
}
if ((string) ($user['role'] ?? '') !== 'admin') {
    redirect_to('/');
}

$csrfToken = Csrf::token();
$facultyService = $app->facultyService();
$completionService = $app->completionService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check(is_string($_POST['_csrf'] ?? null) ? (string) $_POST['_csrf'] : null)) {
        Session::flash('flash', ['type' => 'error', 'message' => 'The request could not be verified. Refresh the page and try again.']);
        redirect_to('/admin/dashboard.php');
    }

    $action = (string) ($_POST['action'] ?? '');
    $id = trim((string) ($_POST['id'] ?? ''));

    if ($action === 'delete' && $id !== '') {
        try {
            $facultyService->delete($id, $user);
            Session::flash('flash', ['type' => 'success', 'message' => 'The faculty profile was deleted.']);
        } catch (ApiException $exception) {
            Session::flash('flash', ['type' => 'error', 'message' => $exception->getMessage()]);
        } catch (Throwable) {
            Session::flash('flash', ['type' => 'error', 'message' => 'The profile could not be deleted because of a server error.']);
        }
    }

    redirect_to('/admin/dashboard.php');
}

$flash = pull_flash();

$q = trim((string) ($_GET['q'] ?? ''));
$department = trim((string) ($_GET['department'] ?? ''));
$designation = trim((string) ($_GET['designation'] ?? ''));

$allProfiles = $facultyService->all();
$stats = $completionService->directoryStats($allProfiles);
$dean = $facultyService->dean();

$result = $facultyService->list([
    'q' => $q,
    'department' => $department,
    'designation' => $designation,
    'includeDean' => '1',
    'pageSize' => 200,
]);
$profiles = $result['items'];
usort($profiles, static function (array $a, array $b): int {
    $orderA = (int) ($a['displayOrder'] ?? 0);
    $orderB = (int) ($b['displayOrder'] ?? 0);
    if ($orderA !== $orderB) {
        return $orderA <=> $orderB;
    }

    return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
});

$hasFilters = $q !== '' || $department !== '' || $designation !== '';

$sidebarLinks = [
    ['key' => 'overview', 'label' => 'Admin Dashboard', 'href' => app_url('/admin/dashboard.php'), 'icon' => 'speedometer2'],
    ['key' => 'create', 'label' => 'Create Profile', 'href' => app_url('/admin/edit.php'), 'icon' => 'person-plus'],
    ['key' => 'directory', 'label' => 'Staff list', 'href' => app_url('/'), 'icon' => 'people-fill'],
];
if ($dean !== null) {
    $sidebarLinks[] = ['key' => 'dean', 'label' => 'Dean Profile', 'href' => app_url('/faculty/view.php?id=' . rawurlencode((string) $dean['id'])), 'icon' => 'award-fill'];
}

$pageTitle = 'Administrator dashboard';
$pageHeading = 'Administrator dashboard';
$activeNav = 'overview';
$useSidebar = true;

ob_start();
?>
<section class="workspace-hero reveal">
  <div class="workspace-hero__identity">
    <span class="avatar avatar--xl" style="--av-a: #5A3BD4; --av-b: #9E86F5" aria-hidden="true">AD</span>
    <div class="workspace-hero__copy">
      <span class="eyebrow"><i class="bi bi-shield-lock-fill" aria-hidden="true"></i> Administrator workspace</span>
      <h2>Manage every faculty profile</h2>
      <p>Create, edit, review and remove profiles. Directory statistics exclude the dean, matching the public directory.</p>
      <div class="workspace-hero__meta">
        <span class="badge badge--muted"><i class="bi bi-person-badge" aria-hidden="true"></i> <?= e((string) ($user['email'] ?? '')) ?></span>
        <span class="badge"><i class="bi bi-clock" aria-hidden="true"></i> <?= e((string) (($user['lastLoginAt'] ?? null) ?: 'This session')) ?></span>
      </div>
    </div>
  </div>
  <div class="workspace-hero__actions">
    <a class="btn btn-primary" href="<?= e(app_url('/admin/edit.php')) ?>"><i class="bi bi-person-plus" aria-hidden="true"></i> Create profile</a>
    <a class="btn btn-soft" href="<?= e(app_url('/')) ?>"><i class="bi bi-people" aria-hidden="true"></i> Staff list</a>
    <button class="btn btn-ghost" type="button" data-print-page><i class="bi bi-printer" aria-hidden="true"></i> Print overview</button>
  </div>
</section>

<div class="summary-grid reveal" aria-label="Administrator summary">
  <article class="summary-tile">
    <span>Total profiles</span>
    <strong><?= count($allProfiles) ?></strong>
    <small><?= $stats['total'] ?> in the directory, <?= $dean !== null ? 1 : 0 ?> dean profile.</small>
  </article>
  <article class="summary-tile">
    <span>Complete</span>
    <strong><?= (int) $stats['complete'] ?></strong>
    <small>All 15 sections filled. <?= (int) $stats['rate'] ?>% directory completion.</small>
  </article>
  <article class="summary-tile">
    <span>In progress</span>
    <strong><?= (int) $stats['incomplete'] ?></strong>
    <small>Profiles missing one or more sections.</small>
  </article>
  <article class="summary-tile">
    <span>Departments</span>
    <strong><?= count($stats['departments']) ?></strong>
    <small>Departments represented in the directory.</small>
  </article>
</div>

<?php if ($dean !== null): ?>
<section class="dean-card reveal" aria-labelledby="admin-dean-name">
  <div class="dean-card__avatar"><?= render_avatar($dean, 'avatar--lg') ?></div>
  <div class="dean-card__copy">
    <span class="dean-card__eyebrow"><i class="bi bi-award-fill" aria-hidden="true"></i> Office of the Dean</span>
    <h2 id="admin-dean-name"><?= e((string) ($dean['name'] ?? 'Dean')) ?></h2>
    <p class="dean-card__designation"><?= e(format_value($dean['designation'] ?? '', 'Dean')) ?></p>
    <p class="dean-card__department"><i class="bi bi-envelope" aria-hidden="true"></i> <?= e(format_value($dean['email'] ?? '', 'Contact not published')) ?></p>
  </div>
  <div class="dean-card__actions">
    <span class="dean-card__tag"><i class="bi bi-shield-check" aria-hidden="true"></i> Protected profile</span>
    <a class="btn btn-white" href="<?= e(app_url('/admin/edit.php?id=' . rawurlencode((string) $dean['id']))) ?>">Edit dean profile <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
  </div>
</section>
<?php endif; ?>

<section class="card admin-overview reveal" aria-labelledby="department-title">
  <div class="admin-overview__head">
    <div>
      <h2 id="department-title">Department distribution</h2>
      <p class="admin-overview__note">Directory profiles grouped by department. The dean is excluded.</p>
    </div>
  </div>
  <div class="department-list">
    <?php foreach ($stats['departments'] as $departmentRow): ?>
      <?php $share = $stats['total'] > 0 ? (int) round(($departmentRow['count'] / $stats['total']) * 100) : 0; ?>
      <div class="department-row">
        <div class="department-row__top">
          <strong><?= e((string) $departmentRow['department']) ?></strong>
          <span><?= (int) $departmentRow['count'] ?> · <?= $share ?>%</span>
        </div>
        <div class="progress-track"><span style="width: <?= $share ?>%"></span></div>
      </div>
    <?php endforeach; ?>
    <?php if ($stats['departments'] === []): ?>
      <p class="view-note"><i class="bi bi-info-circle" aria-hidden="true"></i> No directory profiles are published yet.</p>
    <?php endif; ?>
  </div>
</section>

<section class="stack reveal" aria-labelledby="profiles-title">
  <div class="section-heading">
    <div>
      <h2 id="profiles-title">All faculty profiles</h2>
      <p><?= count($profiles) ?> profile<?= count($profiles) === 1 ? '' : 's' ?><?= $hasFilters ? ' match your filters' : ' in the repository' ?>.</p>
    </div>
    <div class="section-heading__actions">
      <a class="btn btn-primary btn-sm" href="<?= e(app_url('/admin/edit.php')) ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> New profile</a>
    </div>
  </div>

  <form class="table-toolbar" method="get" action="<?= e(app_url('/admin/dashboard.php')) ?>" role="search">
    <div class="field">
      <label for="admin-q">Search</label>
      <input id="admin-q" type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email, employee ID or specialization" autocomplete="off">
    </div>
    <div class="field">
      <label for="admin-department">Department</label>
      <select id="admin-department" name="department">
        <option value="">All departments</option>
        <?php foreach (Enums::DEPARTMENTS as $option): ?>
          <option value="<?= e($option) ?>"<?= $department === $option ? ' selected' : '' ?>><?= e($option) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="admin-designation">Designation</label>
      <select id="admin-designation" name="designation">
        <option value="">All designations</option>
        <?php foreach (Enums::DESIGNATIONS as $option): ?>
          <option value="<?= e($option) ?>"<?= $designation === $option ? ' selected' : '' ?>><?= e($option) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="table-toolbar__actions">
      <button class="btn btn-primary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i> Filter</button>
      <?php if ($hasFilters): ?>
        <a class="btn btn-ghost" href="<?= e(app_url('/admin/dashboard.php')) ?>">Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if ($profiles === []): ?>
    <div class="empty-state">
      <span class="empty-state__icon"><i class="bi bi-person-x" aria-hidden="true"></i></span>
      <h3>No profiles found</h3>
      <p>Adjust the filters, or create the first faculty profile.</p>
      <a class="btn btn-primary" href="<?= e(app_url('/admin/edit.php')) ?>"><i class="bi bi-person-plus" aria-hidden="true"></i> Create profile</a>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <caption>Every profile in the repository, including the dean. Select an action to view, edit or delete.</caption>
        <thead>
          <tr>
            <th scope="col">Faculty member</th>
            <th scope="col">Department</th>
            <th scope="col">Designation</th>
            <th scope="col">Completion</th>
            <th scope="col">Flags</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($profiles as $profile): ?>
            <?php
            $completion = $completionService->calculate($profile);
            $percent = (int) ($completion['percent'] ?? 0);
            $isDean = ($profile['isDean'] ?? false) === true;
            $viewUrl = app_url('/faculty/view.php?id=' . rawurlencode((string) $profile['id']));
            $editUrl = app_url('/admin/edit.php?id=' . rawurlencode((string) $profile['id']));
            ?>
            <tr>
              <td>
                <div class="table-profile">
                  <?= render_avatar($profile, 'avatar--sm') ?>
                  <span class="table-profile__copy">
                    <strong><?= e((string) ($profile['name'] ?? 'Unnamed profile')) ?></strong>
                    <span><?= e(format_value($profile['facultyId'] ?? '', 'No employee ID')) ?> · <?= e(format_value($profile['email'] ?? '', 'No email')) ?></span>
                  </span>
                </div>
              </td>
              <td><?= e(format_value($profile['department'] ?? '')) ?></td>
              <td><?= e(format_value($profile['designation'] ?? '')) ?></td>
              <td>
                <div class="table-progress">
                  <div class="progress-track"><span style="width: <?= $percent ?>%"></span></div>
                  <span><?= $percent ?>%</span>
                </div>
              </td>
              <td>
                <div class="badge-row">
                  <?php if ($isDean): ?><span class="badge"><i class="bi bi-award-fill" aria-hidden="true"></i> Dean</span><?php endif; ?>
                  <span class="<?= e(profile_badge_class((string) ($completion['badge']['tone'] ?? 'start'))) ?>"><?= e((string) ($completion['badge']['label'] ?? 'Profile')) ?></span>
                </div>
              </td>
              <td>
                <div class="table-actions">
                  <a class="btn btn-ghost" href="<?= e($viewUrl) ?>" title="View public profile"><i class="bi bi-eye" aria-hidden="true"></i><span>View</span></a>
                  <a class="btn btn-soft" href="<?= e($editUrl) ?>" title="Edit profile"><i class="bi bi-pencil-square" aria-hidden="true"></i><span>Edit</span></a>
                  <?php if (!$isDean): ?>
                    <form method="post" action="<?= e(app_url('/admin/dashboard.php')) ?>" data-confirm="Delete this profile permanently? This cannot be undone.">
                      <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= e((string) $profile['id']) ?>">
                      <button class="btn btn-danger" type="submit" title="Delete profile"><i class="bi bi-trash3" aria-hidden="true"></i><span>Delete</span></button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php
$content = ob_get_clean();

require dirname(__DIR__, 2) . '/views/layout.php';
