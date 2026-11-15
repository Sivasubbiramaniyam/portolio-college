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
$completion = $profile !== null ? $app->completionService()->calculate($profile) : null;

$sidebarLinks = [
    ['key' => 'overview', 'label' => 'My Workspace', 'href' => app_url('/staff/workspace.php'), 'icon' => 'speedometer2'],
];
if ($profile !== null) {
    $sidebarLinks[] = ['key' => 'edit', 'label' => 'Edit Profile', 'href' => app_url('/staff/edit.php'), 'icon' => 'pencil-square'];
    $sidebarLinks[] = ['key' => 'public', 'label' => 'Public Profile', 'href' => app_url('/faculty/view.php?id=' . rawurlencode((string) $profile['id'])), 'icon' => 'box-arrow-up-right'];
}
$sidebarLinks[] = ['key' => 'directory', 'label' => 'Staff list', 'href' => app_url('/'), 'icon' => 'people-fill'];

$pageTitle = 'My workspace';
$pageHeading = 'My workspace';
$activeNav = 'overview';
$useSidebar = true;

$sectionAnchors = [
    'personal' => 'section-identity',
    'specializations' => 'section-identity',
    'qualification' => 'section-qualifications',
    'experience' => 'section-experience',
    'publications' => 'section-publications',
    'conferencesAttended' => 'section-conferencesAttended',
    'conferencesConducted' => 'section-conferencesConducted',
    'trainingPrograms' => 'section-trainingPrograms',
    'patents' => 'section-patents',
    'projects' => 'section-projects',
    'researchAreas' => 'section-researchAreas',
    'achievements' => 'section-achievements',
    'certifications' => 'section-certifications',
    'responsibilities' => 'section-responsibilities',
    'researchIds' => 'section-researchIds',
];

ob_start();
?>

<?php if ($profile === null): ?>
  <div class="empty-state">
    <span class="empty-state__icon"><i class="bi bi-person-exclamation" aria-hidden="true"></i></span>
    <h3>No faculty profile is linked to this account</h3>
    <p>Your account is active, but an administrator has not linked it to a faculty profile yet. Ask an administrator to connect your account, or contact the portal maintainer.</p>
    <div class="inline-actions">
      <a class="btn btn-primary" href="<?= e(app_url('/')) ?>"><i class="bi bi-people" aria-hidden="true"></i> Browse staff list</a>
      <form method="post" action="<?= e(app_url('/logout.php')) ?>">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <button class="btn btn-ghost" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sign out</button>
      </form>
    </div>
  </div>
<?php else: ?>
  <?php
  $percent = (int) ($completion['percent'] ?? 0);
  $filled = (int) ($completion['filled'] ?? 0);
  $publications = is_array($profile['publications'] ?? null) ? count($profile['publications']) : 0;
  $projects = is_array($profile['projects'] ?? null) ? count($profile['projects']) : 0;
  $patents = is_array($profile['patents'] ?? null) ? count($profile['patents']) : 0;
  $badgeClass = profile_badge_class((string) ($completion['badge']['tone'] ?? 'start'));
  ?>

  <section class="workspace-hero reveal">
    <div class="workspace-hero__identity">
      <?= render_avatar($profile, 'avatar--xl') ?>
      <div class="workspace-hero__copy">
        <span class="eyebrow"><i class="bi bi-person-badge" aria-hidden="true"></i> Faculty workspace</span>
        <h2><?= e((string) ($profile['name'] ?? 'Faculty member')) ?></h2>
        <p><?= e(format_value($profile['designation'] ?? '', 'Faculty member')) ?><?= trim((string) ($profile['department'] ?? '')) !== '' ? ' · ' . e((string) $profile['department']) : '' ?></p>
        <div class="workspace-hero__meta">
          <span class="badge badge--muted"><i class="bi bi-person-vcard" aria-hidden="true"></i> <?= e(format_value($profile['facultyId'] ?? '', 'ID pending')) ?></span>
          <span class="<?= e($badgeClass) ?>"><i class="bi bi-check2-circle" aria-hidden="true"></i> <?= e((string) ($completion['badge']['label'] ?? 'Profile')) ?> · <?= $percent ?>%</span>
          <?php if (($profile['isDean'] ?? false) === true): ?>
            <span class="badge"><i class="bi bi-award-fill" aria-hidden="true"></i> Dean</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="workspace-hero__actions">
      <a class="btn btn-primary" href="<?= e(app_url('/staff/edit.php')) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i> Edit my profile</a>
      <a class="btn btn-soft" href="<?= e(app_url('/faculty/view.php?id=' . rawurlencode((string) $profile['id']))) ?>"><i class="bi bi-eye" aria-hidden="true"></i> View public profile</a>
      <button class="btn btn-ghost" type="button" data-print-page><i class="bi bi-printer" aria-hidden="true"></i> Print CV</button>
    </div>
  </section>

  <section class="summary-grid reveal" aria-label="Profile summary">
    <article class="summary-tile">
      <span>Sections complete</span>
      <strong><?= $filled ?> / 15</strong>
      <small><?= $filled === 15 ? 'Everything is published.' : (15 - $filled) . ' section' . ((15 - $filled) === 1 ? '' : 's') . ' still to complete.' ?></small>
    </article>
    <article class="summary-tile">
      <span>Completion</span>
      <strong><?= $percent ?>%</strong>
      <small><?= e((string) ($completion['badge']['label'] ?? 'Profile')) ?> status</small>
    </article>
    <article class="summary-tile">
      <span>Publications</span>
      <strong><?= $publications ?></strong>
      <small>Journal articles, conference papers and books.</small>
    </article>
    <article class="summary-tile">
      <span>Funded projects</span>
      <strong><?= $projects ?></strong>
      <small><?= $patents ?> patent<?= $patents === 1 ? '' : 's' ?> recorded.</small>
    </article>
  </section>

  <section class="card admin-overview reveal" aria-labelledby="completion-title">
    <div class="admin-overview__head">
      <div>
        <h2 id="completion-title">Profile completion</h2>
        <p class="admin-overview__note">Fifteen equally weighted sections. Select a section to jump straight to the editor.</p>
      </div>
      <span class="profile-section__count"><?= $percent ?>%</span>
    </div>
    <div class="progress-track"><span style="width: <?= $percent ?>%"></span></div>
    <div class="checklist-grid">
      <?php foreach (($completion['sections'] ?? []) as $section): ?>
        <?php $anchor = $sectionAnchors[(string) ($section['key'] ?? '')] ?? 'section-identity'; ?>
        <a class="checklist-item<?= !empty($section['complete']) ? ' is-complete' : '' ?>" href="<?= e(app_url('/staff/edit.php#' . $anchor)) ?>">
          <i class="bi bi-<?= !empty($section['complete']) ? 'check-circle-fill' : 'circle' ?>" aria-hidden="true"></i>
          <span><?= e((string) ($section['label'] ?? '')) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="card admin-overview reveal" aria-labelledby="profile-snapshot-title">
    <div class="admin-overview__head">
      <div>
        <h2 id="profile-snapshot-title">Public profile snapshot</h2>
        <p class="admin-overview__note">This is how your identity appears in the directory.</p>
      </div>
      <a class="btn btn-soft btn-sm" href="<?= e(app_url('/staff/edit.php')) ?>"><i class="bi bi-pencil" aria-hidden="true"></i> Update details</a>
    </div>

    <div class="info-grid">
      <?= profile_info_item('Full name', e((string) ($profile['name'] ?? '')), false) ?>
      <?= profile_info_item('Employee / faculty ID', e(format_value($profile['facultyId'] ?? ''))) ?>
      <?= profile_info_item('Department', e(format_value($profile['department'] ?? ''))) ?>
      <?= profile_info_item('Designation', e(format_value($profile['designation'] ?? ''))) ?>
      <?= profile_info_item('Email', trim((string) ($profile['email'] ?? '')) !== '' ? '<a href="mailto:' . e((string) $profile['email']) . '">' . e((string) $profile['email']) . '</a>' : '—') ?>
      <?= profile_info_item('Contact', e(format_value($profile['contact'] ?? ''))) ?>
    </div>
    <?php $tags = split_tags($profile['specialization'] ?? ''); ?>
    <?php if ($tags !== []): ?>
      <div class="specialization-grid" style="margin-top:16px">
        <?php foreach ($tags as $tag): ?>
          <div class="specialization-item">
            <span class="specialization-item__icon"><i class="bi bi-<?= e(specialization_icon($tag)) ?>" aria-hidden="true"></i></span>
            <span class="specialization-item__label"><?= e($tag) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="card admin-overview reveal" aria-labelledby="account-title">
    <div class="admin-overview__head">
      <div>
        <h2 id="account-title">Account</h2>
        <p class="admin-overview__note">Session and account details for this sign-in.</p>
      </div>
    </div>
    <div class="info-grid">
      <?= profile_info_item('Email', e((string) ($user['email'] ?? ''))) ?>
      <?= profile_info_item('Role', e((string) ($user['roleLabel'] ?? 'Faculty member'))) ?>
      <?= profile_info_item('Last sign-in', e((string) (($user['lastLoginAt'] ?? null) ?: 'This session'))) ?>
      <?= profile_info_item('Linked profile', e((string) ($profile['id'] ?? ''))) ?>
    </div>
  </section>
<?php endif; ?>
<?php
$content = ob_get_clean();

require dirname(__DIR__, 2) . '/views/layout.php';
