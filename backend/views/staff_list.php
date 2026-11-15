<?php

declare(strict_types=1);

/**
 * Plain-HTML, third-person staff listing.
 *
 * Deliberately dependency-free (no portal layout, no external CSS) so the page
 * can be reviewed and later replaced or removed without touching the rest of
 * the frontend.
 */

if (!function_exists('render_staff_list_document')) {
    /**
     * @param array<int, array<string, mixed>> $profiles
     * @param array{user?: array<string, mixed>|null, csrfToken?: string, heading?: string} $options
     */
    function render_staff_list_document(array $profiles, array $options = []): string
    {
        $user = $options['user'] ?? null;
        $csrfToken = (string) ($options['csrfToken'] ?? '');
        $heading = (string) ($options['heading'] ?? 'Faculty staff');

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($heading) ?> | UCEN Faculty Portal</title>
  <style>
    body { margin: 0; padding: 24px; font-family: system-ui, -apple-system, "Segoe UI", Arial, sans-serif; color: #1c1c1c; background: #fff; }
    h1 { margin: 0 0 4px; font-size: 1.5rem; }
    .lede { margin: 0 0 18px; color: #555; }
    .bar { display: flex; flex-wrap: wrap; gap: 14px; align-items: center; margin: 0 0 20px; padding: 0; list-style: none; }
    .bar a, .bar button { color: #4b2ecc; }
    table { border-collapse: collapse; width: 100%; max-width: 980px; }
    th, td { border: 1px solid #cfcfcf; padding: 8px 10px; text-align: left; font-size: 0.95rem; vertical-align: top; }
    th { background: #f3f0ff; }
    form.signout { display: inline; margin: 0; }
    button.link { padding: 0; border: 0; background: none; font: inherit; text-decoration: underline; cursor: pointer; }
    .muted { color: #666; }
  </style>
</head>
<body>
  <h1><?= e($heading) ?></h1>
  <p class="lede">Public listing of faculty members. Select &ldquo;View profile&rdquo; to open a profile.</p>

  <ul class="bar">
    <li><a href="<?= e(app_url('/staff-list.php')) ?>">Staff list</a></li>
    <?php if (is_array($user)): ?>
      <li>
        <form class="signout" method="post" action="<?= e(app_url('/logout.php')) ?>">
          <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
          <button class="link" type="submit">Sign out</button>
        </form>
      </li>
    <?php endif; ?>
  </ul>

  <?php if ($profiles === []): ?>
    <p class="muted">No staff profiles are published yet.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th scope="col">Name</th>
          <th scope="col">Department</th>
          <th scope="col">Designation</th>
          <th scope="col">Profile</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($profiles as $profile): ?>
          <tr>
            <td><?= e((string) ($profile['name'] ?? 'Unnamed profile')) ?></td>
            <td><?= e(format_value($profile['department'] ?? '')) ?></td>
            <td><?= e(format_value($profile['designation'] ?? '')) ?></td>
            <td><a href="<?= e(app_url('/faculty/view.php?id=' . rawurlencode((string) ($profile['id'] ?? '')))) ?>">View profile</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</body>
</html>
        <?php
        return (string) ob_get_clean();
    }
}
