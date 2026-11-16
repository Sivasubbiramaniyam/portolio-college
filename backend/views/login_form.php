<?php

declare(strict_types=1);

/**
 * Shared sign-in panel used by the staff and administrator login pages.
 *
 * There is deliberately no role selector here: each login URL is bound to a
 * single role, so the radio/card selector is not needed. Sign-in uses an
 * identifier (staff ID or admin ID) plus password, mirroring production.
 */

if (!function_exists('render_login_panel')) {
    /**
     * @param array{
     *   action: string,
     *   csrfToken: string,
     *   role?: string,
     *   eyebrow?: string,
     *   title?: string,
     *   intro?: string,
     *   identifier?: string,
     *   identifierLabel?: string,
     *   identifierPlaceholder?: string,
     *   identifierHint?: string,
     *   errors?: array<string, string>,
     *   message?: string,
     *   notice?: string,
     *   signedOut?: bool,
     *   authConfigured?: bool,
     *   showPublicLink?: bool,
     *   showPasswordHelp?: bool,
     *   altLoginUrl?: string,
     *   altLoginLabel?: string,
     *   registerUrl?: string,
     *   registerLabel?: string
     * } $options
     */
    function render_login_panel(array $options): string
    {
        $action = (string) ($options['action'] ?? '');
        $csrfToken = (string) ($options['csrfToken'] ?? '');
        $eyebrow = (string) ($options['eyebrow'] ?? 'Authorised access');
        $title = (string) ($options['title'] ?? 'Sign in');
        $intro = (string) ($options['intro'] ?? '');
        $identifier = (string) ($options['identifier'] ?? '');
        $identifierLabel = (string) ($options['identifierLabel'] ?? 'Staff ID');
        $identifierPlaceholder = (string) ($options['identifierPlaceholder'] ?? '');
        $identifierHint = (string) ($options['identifierHint'] ?? '');
        $errors = (array) ($options['errors'] ?? []);
        $message = (string) ($options['message'] ?? '');
        $notice = (string) ($options['notice'] ?? '');
        $signedOut = (bool) ($options['signedOut'] ?? false);
        $authConfigured = (bool) ($options['authConfigured'] ?? true);
        $showPublicLink = (bool) ($options['showPublicLink'] ?? true);
        $showPasswordHelp = (bool) ($options['showPasswordHelp'] ?? true);
        $altLoginUrl = (string) ($options['altLoginUrl'] ?? '');
        $altLoginLabel = (string) ($options['altLoginLabel'] ?? '');
        $registerUrl = (string) ($options['registerUrl'] ?? '');
        $registerLabel = (string) ($options['registerLabel'] ?? 'Create an account');

        ob_start();
        ?>
<section class="login-shell login-shell--single">
  <div class="login-card login-box card">
    <div class="login-card__head">
      <span class="eyebrow"><i class="bi bi-shield-lock" aria-hidden="true"></i> <?= e($eyebrow) ?></span>
      <h2><?= e($title) ?></h2>
      <?php if ($intro !== ''): ?><p><?= e($intro) ?></p><?php endif; ?>
    </div>

    <?php if (!$authConfigured): ?>
      <div class="portal-alert portal-alert--warning" role="alert">
        <i class="bi bi-tools" aria-hidden="true"></i>
        <div>
          <strong>Authentication is not configured.</strong>
          <p>Create the first account from the project root:</p>
          <pre class="code-block">php backend/bin/make-user.php --role=admin --admin-id=admin --email=you@ucen.ac.in</pre>
        </div>
      </div>
    <?php endif; ?>

    <form class="login-form" method="post" action="<?= e($action) ?>" novalidate>
      <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

      <div class="field<?= isset($errors['identifier']) ? ' is-invalid' : '' ?>">
        <label for="login-identifier"><?= e($identifierLabel) ?></label>
        <input id="login-identifier" type="text" name="identifier" value="<?= e($identifier) ?>"<?= $identifierPlaceholder !== '' ? ' placeholder="' . e($identifierPlaceholder) . '"' : '' ?> autocomplete="username" autocapitalize="off" spellcheck="false" required autofocus>
        <?php if ($identifierHint !== '' && !isset($errors['identifier'])): ?><small class="field-hint"><?= e($identifierHint) ?></small><?php endif; ?>
        <?php if (isset($errors['identifier'])): ?><span class="field-error"><?= e($errors['identifier']) ?></span><?php endif; ?>
      </div>

      <div class="field<?= isset($errors['password']) ? ' is-invalid' : '' ?>">
        <label for="login-password">Password</label>
        <div class="password-field">
          <input id="login-password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
          <button class="password-toggle" type="button" data-toggle-password="login-password" aria-label="Show password">
            <i class="bi bi-eye" aria-hidden="true"></i>
          </button>
        </div>
        <?php if (isset($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
      </div>

      <button class="btn btn-primary login-submit" type="submit"<?= $authConfigured ? '' : ' disabled' ?>>
        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Sign in
      </button>
    </form>

    <div class="login-help">
      <?php if ($registerUrl !== ''): ?>
        <p><i class="bi bi-person-plus" aria-hidden="true"></i> <a href="<?= e($registerUrl) ?>"><?= e($registerLabel) ?></a></p>
      <?php endif; ?>
      <?php if ($showPublicLink): ?>
        <p><i class="bi bi-info-circle" aria-hidden="true"></i> Public profiles are visible without signing in. <a href="<?= e(app_url('/')) ?>">Browse the staff list</a>.</p>
      <?php endif; ?>
      <?php if ($altLoginUrl !== ''): ?>
        <p><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> <a href="<?= e($altLoginUrl) ?>"><?= e($altLoginLabel) ?> Login</a></p>
      <?php endif; ?>
      <?php if ($showPasswordHelp): ?>
        <p><i class="bi bi-key" aria-hidden="true"></i> Forgotten passwords are reset by an administrator using the CLI user tool.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
        <?php
        return (string) ob_get_clean();
    }
}
