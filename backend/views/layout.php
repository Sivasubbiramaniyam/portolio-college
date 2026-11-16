<?php

declare(strict_types=1);

use App\Core\Config;

/** @var string $content */
/** @var string $pageTitle */
/** @var string $pageHeading */
/** @var array<string, mixed>|null $user */
/** @var string $csrfToken */
/** @var string $activeNav */
/** @var array<int, array{key: string, label: string, href: string, icon: string}> $sidebarLinks */
/** @var bool $useSidebar */
/** @var string $bodyClass */
/** @var array<string, mixed> $flash */
/** @var string $pageActions */

$pageTitle = $pageTitle ?? 'Faculty Portal';
$pageHeading = $pageHeading ?? $pageTitle;
$activeNav = $activeNav ?? '';
$sidebarLinks = $sidebarLinks ?? [];
$useSidebar = $useSidebar ?? true;
$bodyClass = $bodyClass ?? '';
$flash = $flash ?? [];
$pageActions = $pageActions ?? '';
/** @var array{href: string, label: string}|null $sidebarBack */
$sidebarBack = $sidebarBack ?? null;
/** @var string $sidebarTitle */
$sidebarTitle = $sidebarTitle ?? '';
/** @var bool $sidebarPinned */
$sidebarPinned = $sidebarPinned ?? false;
/** @var array<int, array{label: string, href: string, icon: string}> $sidebarAccountLinks */
$sidebarAccountLinks = $sidebarAccountLinks ?? [];
$institution = (array) Config::get('institution', []);
$logo = asset_url((string) ($institution['logoUrl'] ?? '/frontend/assets/anna-university-logo.svg'));
$isAdmin = is_array($user) && (string) ($user['role'] ?? '') === 'admin';
$isFaculty = is_array($user) && (string) ($user['role'] ?? '') === 'faculty';
$homeLink = app_url('/');
if ($isAdmin) {
    $homeLink = app_url('/admin/dashboard.php');
} elseif ($isFaculty) {
    $homeLink = app_url('/staff/workspace.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#7B5EF0">
  <meta name="csrf-token" content="<?= e($csrfToken) ?>">
  <meta name="api-base" content="<?= e(app_url('/api/v1')) ?>">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($pageTitle) ?> | UCEN Faculty Portal</title>
  <link rel="icon" type="image/svg+xml" href="<?= e(asset_url('/frontend/assets/anna-university-logo.svg')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= e(asset_url('/frontend/css/style.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset_url('/frontend/css/portal.css')) ?>">
</head>
<body class="portal-page <?= e($bodyClass) ?><?= $sidebarPinned ? ' sidebar-expanded' : '' ?>" data-role="<?= e((string) ($user['role'] ?? 'viewer')) ?>">
  <a class="skip-link" href="#main-content">Skip to main content</a>

  <div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>

  <?php if ($useSidebar): ?>
  <aside class="sidebar<?= $sidebarPinned ? ' is-expanded sidebar--pinned' : '' ?>" id="sidebar" aria-label="Portal navigation">
    <a class="sidebar__brand" href="<?= e($homeLink) ?>" aria-label="Home">
      <span class="sidebar__brand-mark"><img src="<?= e($logo) ?>" alt="" width="34" height="34"></span>
    </a>

    <?php if (is_array($sidebarBack) && (string) ($sidebarBack['href'] ?? '') !== ''): ?>
      <a class="sidebar__back" href="<?= e((string) $sidebarBack['href']) ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        <span><?= e((string) ($sidebarBack['label'] ?? 'Back')) ?></span>
      </a>
    <?php endif; ?>

    <nav class="sidebar__nav" aria-label="Portal navigation">
      <?php if ($sidebarTitle !== ''): ?>
        <span class="sidebar__nav-label"><?= e($sidebarTitle) ?></span>
      <?php endif; ?>

      <?php foreach ($sidebarLinks as $link): ?>
        <?php $isActive = $activeNav === (string) ($link['key'] ?? ''); ?>
        <a class="side-link<?= $isActive ? ' is-active' : '' ?>" href="<?= e((string) $link['href']) ?>"<?= $isActive ? ' aria-current="page"' : '' ?> data-sidebar-item data-sidebar-label="<?= e(strtolower((string) $link['label'])) ?>">
          <span class="side-link__icon"><i class="bi bi-<?= e((string) $link['icon']) ?>" aria-hidden="true"></i></span>
          <span class="side-link__label"><?= e((string) $link['label']) ?></span>
        </a>
      <?php endforeach; ?>

      <?php if ($sidebarAccountLinks !== []): ?>
        <span class="sidebar__nav-label">Account</span>
        <?php foreach ($sidebarAccountLinks as $link): ?>
          <a class="side-link" href="<?= e((string) $link['href']) ?>" data-sidebar-item data-sidebar-label="<?= e(strtolower((string) $link['label'])) ?>">
            <span class="side-link__icon"><i class="bi bi-<?= e((string) $link['icon']) ?>" aria-hidden="true"></i></span>
            <span class="side-link__label"><?= e((string) $link['label']) ?></span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </nav>

    <?php if (is_array($user)): ?>
    <div class="sidebar__footer">
      <form class="sidebar__signout" method="post" action="<?= e(app_url('/logout.php')) ?>">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <button class="side-link side-link--button" type="submit">
          <span class="side-link__icon"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></span>
          <span class="side-link__label">Sign out</span>
        </button>
      </form>
    </div>
    <?php endif; ?>

    <button class="sidebar__toggle" id="sidebar-toggle" type="button" aria-expanded="<?= $sidebarPinned ? 'true' : 'false' ?>" aria-controls="sidebar" title="<?= $sidebarPinned ? 'Collapse sidebar' : 'Expand sidebar' ?>" aria-label="<?= $sidebarPinned ? 'Collapse sidebar' : 'Expand sidebar' ?>">
      <i class="bi bi-chevron-<?= $sidebarPinned ? 'left' : 'right' ?>" id="sidebar-toggle-icon" aria-hidden="true"></i>
    </button>
  </aside>
  <?php endif; ?>

  <div class="app-shell<?= $useSidebar ? '' : ' app-shell--plain' ?>">
    <header class="topbar">
      <div class="topbar__inner">
        <div class="topbar__brand">
          <?php if (!$useSidebar): ?>
            <span class="topbar__logo">
              <img src="<?= e($logo) ?>" alt="Portal logo" width="56" height="56">
            </span>
          <?php endif; ?>
          <div class="topbar__identity">
            <h1 class="topbar__page-title"><?= e($pageHeading) ?></h1>
          </div>
        </div>

        <div class="topbar__actions">
          <?= $pageActions ?>
        </div>
      </div>
    </header>

    <main class="main" id="main-content">
      <div class="view">
        <div class="view__inner">
          <?= $content ?>
        </div>
      </div>

      <footer class="footer">
        <div class="footer__brand">
          <span>&copy; <?= date('Y') ?> Faculty Portal</span>
        </div>
      </footer>
    </main>
  </div>

  <div class="toast-stack" id="toast-stack" aria-live="polite" aria-atomic="true">
    <?= render_flash($flash) ?>
    <?php if (!empty($message) && empty($errors)): ?>
      <?= render_toast((string) $message, 'error') ?>
    <?php endif; ?>
    <?php if (!empty($notice) && (empty($authConfigured) || $authConfigured)): ?>
      <?= render_toast(strip_tags((string) $notice), 'info') ?>
    <?php endif; ?>
    <?php if (!empty($signedOut)): ?>
      <?= render_toast('You have been signed out safely.', 'success') ?>
    <?php endif; ?>
  </div>
  <script src="<?= e(asset_url('/frontend/js/portal.js')) ?>" defer></script>
</body>
</html>
