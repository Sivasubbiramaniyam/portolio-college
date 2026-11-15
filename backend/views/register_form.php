<?php

declare(strict_types=1);

/**
 * Shared self-registration panel used by the staff and administrator pages.
 *
 * Registration is whitelist-gated: a staff account needs a pre-approved staff
 * ID (validuser) and an administrator account a pre-approved admin ID
 * (validadmin). The display name for staff comes from the whitelist, so it is
 * never entered here.
 */

if (!function_exists('render_register_panel')) {
    /**
     * @param array{
     *   action: string,
     *   csrfToken: string,
     *   role: string,
     *   eyebrow?: string,
     *   title?: string,
     *   intro?: string,
     *   identifierLabel?: string,
     *   identifierPlaceholder?: string,
     *   identifierHint?: string,
     *   values?: array<string, string>,
     *   errors?: array<string, string>,
     *   message?: string,
     *   notice?: string,
     *   authConfigured?: bool,
     *   loginUrl?: string,
     *   loginLabel?: string
     * } $options
     */
    function render_register_panel(array $options): string
    {
        $action = (string) ($options['action'] ?? '');
        $csrfToken = (string) ($options['csrfToken'] ?? '');
        $role = (string) ($options['role'] ?? 'staff');
        $isAdmin = $role === 'admin';
        $eyebrow = (string) ($options['eyebrow'] ?? 'Account registration');
        $title = (string) ($options['title'] ?? 'Create your account');
        $intro = (string) ($options['intro'] ?? '');
        $identifierLabel = (string) ($options['identifierLabel'] ?? ($isAdmin ? 'Admin ID' : 'Staff ID'));
        $identifierPlaceholder = (string) ($options['identifierPlaceholder'] ?? '');
        $identifierHint = (string) ($options['identifierHint'] ?? '');
        $values = (array) ($options['values'] ?? []);
        $errors = (array) ($options['errors'] ?? []);
        $message = (string) ($options['message'] ?? '');
        $notice = (string) ($options['notice'] ?? '');
        $authConfigured = (bool) ($options['authConfigured'] ?? true);
        $loginUrl = (string) ($options['loginUrl'] ?? '');
        $loginLabel = (string) ($options['loginLabel'] ?? 'Sign in');

        $value = static fn (string $key): string => (string) ($values[$key] ?? '');
        $invalid = static fn (string $key): string => isset($errors[$key]) ? ' is-invalid' : '';
        $error = static function (string $key) use ($errors): string {
            return isset($errors[$key]) ? '<span class="field-error">' . e($errors[$key]) . '</span>' : '';
        };

        ob_start();
        ?>
<section class="login-shell login-shell--single">
  <div class="login-card card">
    <div class="login-card__head">
      <span class="eyebrow"><i class="bi bi-person-plus" aria-hidden="true"></i> <?= e($eyebrow) ?></span>
      <h2><?= e($title) ?></h2>
      <?php if ($intro !== ''): ?><p><?= e($intro) ?></p><?php endif; ?>
    </div>

    <?php if (!$authConfigured): ?>
      <div class="portal-alert portal-alert--warning" role="alert">
        <i class="bi bi-tools" aria-hidden="true"></i>
        <div><strong>Registration is unavailable.</strong><p>No staff or administrator IDs have been approved yet.</p></div>
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
      <?= render_error_summary($errors, 'Please check the registration form.') ?>
    <?php endif; ?>

    <form class="login-form" method="post" action="<?= e($action) ?>" novalidate>
      <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

      <div class="field<?= $invalid('identifier') ?>">
        <label for="reg-identifier"><?= e($identifierLabel) ?></label>
        <input id="reg-identifier" type="text" name="identifier" value="<?= e($value('identifier')) ?>"<?= $identifierPlaceholder !== '' ? ' placeholder="' . e($identifierPlaceholder) . '"' : '' ?> autocomplete="username" autocapitalize="off" spellcheck="false" required autofocus>
        <?php if ($identifierHint !== '' && !isset($errors['identifier'])): ?><small class="field-hint"><?= e($identifierHint) ?></small><?php endif; ?>
        <?= $error('identifier') ?>
      </div>

      <?php if ($isAdmin): ?>
      <div class="field<?= $invalid('name') ?>">
        <label for="reg-name">Full name</label>
        <input id="reg-name" type="text" name="name" value="<?= e($value('name')) ?>" placeholder="Your full name" autocomplete="name" required>
        <?= $error('name') ?>
      </div>
      <?php endif; ?>

      <div class="field<?= $invalid('email') ?>">
        <label for="reg-email">Email address</label>
        <input id="reg-email" type="email" name="email" value="<?= e($value('email')) ?>" placeholder="name@ucen.ac.in" autocomplete="email" required>
        <?= $error('email') ?>
      </div>

      <?php if (!$isAdmin): ?>
      <div class="field<?= $invalid('department') ?>">
        <label for="reg-department">Department <span class="field-optional">(optional)</span></label>
        <input id="reg-department" type="text" name="department" value="<?= e($value('department')) ?>" placeholder="Leave blank to use your department on file" autocomplete="organization">
        <small class="field-hint">Prefilled from your faculty profile when one matches your Staff ID.</small>
        <?= $error('department') ?>
      </div>
      <?php endif; ?>

      <div class="field<?= $invalid('password') ?>">
        <label for="reg-password">Password</label>
        <div class="password-field">
          <input id="reg-password" type="password" name="password" placeholder="<?= $isAdmin ? 'At least 8 characters' : '8 to 16 characters' ?>" autocomplete="new-password" required>
          <button class="password-toggle" type="button" data-toggle-password="reg-password" aria-label="Show password">
            <i class="bi bi-eye" aria-hidden="true"></i>
          </button>
        </div>
        <?= $error('password') ?>
      </div>

      <div class="field<?= $invalid('confirmPassword') ?>">
        <label for="reg-confirm">Confirm password</label>
        <div class="password-field">
          <input id="reg-confirm" type="password" name="confirmPassword" placeholder="Re-enter your password" autocomplete="new-password" required>
          <button class="password-toggle" type="button" data-toggle-password="reg-confirm" aria-label="Show password">
            <i class="bi bi-eye" aria-hidden="true"></i>
          </button>
        </div>
        <?= $error('confirmPassword') ?>
      </div>

      <button class="btn btn-primary login-submit" type="submit"<?= $authConfigured ? '' : ' disabled' ?>>
        <i class="bi bi-person-plus" aria-hidden="true"></i> Create account
      </button>
    </form>

    <div class="login-help">
      <p><i class="bi bi-info-circle" aria-hidden="true"></i> Your ID must be approved by the administration before you can register.</p>
      <?php if ($loginUrl !== ''): ?>
        <p><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Already registered? <a href="<?= e($loginUrl) ?>"><?= e($loginLabel) ?></a>.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
        <?php
        return (string) ob_get_clean();
    }
}
