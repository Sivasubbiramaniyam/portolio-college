<?php

declare(strict_types=1);

/**
 * Shared sign-in panel used by the staff and administrator login pages.
 *
 * There is deliberately no role selector here: each login URL is bound to a
 * single role, so the radio/card selector is not needed.
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
     *   email?: string,
     *   errors?: array<string, string>,
     *   message?: string,
     *   notice?: string,
     *   signedOut?: bool,
     *   authConfigured?: bool,
     *   altLoginUrl?: string,
     *   altLoginLabel?: string
     * } $options
     */
    function render_login_panel(array $options): string
    {
        $action = (string) ($options['action'] ?? '');
        $csrfToken = (string) ($options['csrfToken'] ?? '');
        $eyebrow = (string) ($options['eyebrow'] ?? 'Authorised access');
        $title = (string) ($options['title'] ?? 'Sign in');
        $intro = (string) ($options['intro'] ?? '');
        $email = (string) ($options['email'] ?? '');
        $errors = (array) ($options['errors'] ?? []);
        $message = (string) ($options['message'] ?? '');
        $notice = (string) ($options['notice'] ?? '');
        $signedOut = (bool) ($options['signedOut'] ?? false);
        $authConfigured = (bool) ($options['authConfigured'] ?? true);
        $altLoginUrl = (string) ($options['altLoginUrl'] ?? '');
        $altLoginLabel = (string) ($options['altLoginLabel'] ?? '');

        ob_start();
        ?>
<section class="login-shell login-shell--single">
  <div class="login-card card">
    <div class="login-card__head">
      <span class="eyebrow"><i class="bi bi-shield-lock" aria-hidden="true"></i> <?= e($eyebrow) ?></span>
      <h2><?= e($title) ?></h2>
      <?php if ($intro !== ''): ?><p><?= e($intro) ?></p><?php endif; ?>
    </div>

    <?php if ($signedOut): ?>
      <div class="portal-alert portal-alert--success" role="status">
        <i class="bi bi-check2-circle" aria-hidden="true"></i>
        <div>You have been signed out safely.</div>
      </div>
    <?php endif; ?>

    <?php if (!$authConfigured): ?>
      <div class="portal-alert portal-alert--warning" role="alert">
        <i class="bi bi-tools" aria-hidden="true"></i>
        <div>
          <strong>Authentication is not configured.</strong>
          <p>Create the first account from the project root:</p>
          <pre class="code-block">php backend/bin/make-user.php --email=you@ucen.ac.in --role=admin</pre>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($message !== ''): ?>
      <div class="portal-alert portal-alert--error" role="alert">
        <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
        <div><?= e($message) ?></div>
      </div>
    <?php endif; ?>

    <?php if ($notice !== ''): ?>
      <div class="portal-alert portal-alert--warning" role="alert">
        <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
        <div><?= $notice ?></div>
      </div>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
      <?= render_error_summary($errors, 'Please check the sign-in form.') ?>
    <?php endif; ?>

    <form class="login-form" method="post" action="<?= e($action) ?>" novalidate>
      <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

      <div class="field<?= isset($errors['email']) ? ' is-invalid' : '' ?>">
        <label for="login-email">Email address</label>
        <input id="login-email" type="email" name="email" value="<?= e($email) ?>" placeholder="name@ucen.ac.in" autocomplete="username" required autofocus>
        <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
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
      <p><i class="bi bi-info-circle" aria-hidden="true"></i> Public profiles are visible without signing in. <a href="<?= e(app_url('/')) ?>">Browse the staff list</a>.</p>
      <?php if ($altLoginUrl !== ''): ?>
        <p><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> <?= e($altLoginLabel) ?>: <a href="<?= e($altLoginUrl) ?>">sign in here</a>.</p>
      <?php endif; ?>
      <p><i class="bi bi-key" aria-hidden="true"></i> Forgotten passwords are reset by an administrator using the CLI user tool.</p>
    </div>
  </div>
</section>
        <?php
        return (string) ob_get_clean();
    }
}
