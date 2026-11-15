<?php

declare(strict_types=1);

use App\Core\ApiException;
use App\Core\Csrf;
use App\Core\ProfileShape;
use App\Core\SecurityHeaders;
use App\Core\Session;

/** @var \App\Core\Application $app */
$app = require dirname(__DIR__, 2) . '/bootstrap.php';

SecurityHeaders::send();
Session::start((array) $app->config('session', []));

require_once dirname(__DIR__, 2) . '/views/helpers.php';
require_once dirname(__DIR__, 2) . '/views/form_view.php';

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

/** @var array<string, mixed> $formConfig */
$formConfig = require dirname(__DIR__, 2) . '/views/form_config.php';

$profileId = (string) ($user['facultyProfileId'] ?? '');
$profile = $profileId !== '' ? $app->facultyService()->maybeFind($profileId) : null;
if ($profile === null) {
    Session::flash('flash', [
        'type' => 'error',
        'message' => 'No faculty profile is linked to this account. Ask an administrator to connect your account before editing.',
    ]);
    redirect_to('/staff/workspace.php');
}

$csrfToken = Csrf::token();
$errors = [];
$message = '';
$formProfile = $profile;
$status = 200;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check(is_string($_POST['_csrf'] ?? null) ? (string) $_POST['_csrf'] : null)) {
        $status = 419;
        $message = 'Your session expired while the form was open. Refresh the page and try again.';
    } else {
        $input = collect_profile_form_input($_POST, $formConfig, $profile, false);

        foreach (['photo', 'banner'] as $media) {
            $fileKey = $media . '_file';
            $file = $_FILES[$fileKey] ?? null;
            if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            try {
                $asset = $app->uploadService()->store($file, $media, (string) $profile['id'], (string) ($user['id'] ?? ''));
                $input[$media] = (string) ($asset['url'] ?? '');
            } catch (ApiException $exception) {
                $errors[$fileKey] = $exception->getMessage();
            } catch (Throwable $exception) {
                $errors[$fileKey] = 'The image could not be stored. Please try a smaller JPEG, PNG or WebP file.';
            }
        }

        if ($errors === []) {
            try {
                $updated = $app->facultyService()->update((string) $profile['id'], $input, false, $user);
                Session::flash('flash', [
                    'type' => 'success',
                    'message' => 'Your profile has been saved. The public directory now shows the updated details.',
                ]);
                redirect_to('/staff/workspace.php');
            } catch (ApiException $exception) {
                if ($exception->errorCode() === 'VALIDATION_ERROR') {
                    $errors = array_merge($errors, $exception->fields());
                    $message = $exception->getMessage();
                    $status = 422;
                } elseif ($exception->errorCode() === 'FORBIDDEN') {
                    $status = 403;
                    $message = 'You can only edit your own faculty profile.';
                } else {
                    $status = $exception->httpStatus();
                    $message = $exception->getMessage();
                }
            } catch (Throwable $exception) {
                $status = 500;
                $message = 'The profile could not be saved because of a server error. Please try again.';
            }
        } else {
            $status = 422;
            $message = 'The profile could not be saved. Please check the highlighted upload fields.';
        }

        if (is_array($input)) {
            $formProfile = ProfileShape::normalize(array_merge($profile, $input));
            $formProfile['id'] = (string) $profile['id'];
            $formProfile['createdAt'] = (string) ($profile['createdAt'] ?? '');
            $formProfile['updatedAt'] = (string) ($profile['updatedAt'] ?? '');
            $formProfile['isDean'] = (bool) ($profile['isDean'] ?? false);
        }
    }
}

if ($status !== 200) {
    http_response_code($status);
}

$flash = pull_flash();
$sidebarLinks = [
    ['key' => 'overview', 'label' => 'My Workspace', 'href' => app_url('/staff/workspace.php'), 'icon' => 'speedometer2'],
    ['key' => 'edit', 'label' => 'Edit Profile', 'href' => app_url('/staff/edit.php'), 'icon' => 'pencil-square'],
    ['key' => 'public', 'label' => 'Public Profile', 'href' => app_url('/faculty/view.php?id=' . rawurlencode((string) $profile['id'])), 'icon' => 'box-arrow-up-right'],
    ['key' => 'directory', 'label' => 'Staff list', 'href' => app_url('/'), 'icon' => 'people-fill'],
];

$pageTitle = 'Edit my profile';
$pageHeading = 'Edit my profile';
$activeNav = 'edit';
$useSidebar = true;

ob_start();
?>
<nav class="view-bar reveal" aria-label="Editor navigation">
  <div>
    <a class="btn btn-ghost" href="<?= e(app_url('/staff/workspace.php')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to workspace</a>
  </div>
  <div class="section-heading__actions">
    <a class="btn btn-soft" href="<?= e(app_url('/faculty/view.php?id=' . rawurlencode((string) $profile['id']))) ?>"><i class="bi bi-eye" aria-hidden="true"></i> View public profile</a>
  </div>
</nav>

<?php if ($message !== ''): ?>
  <div class="portal-alert portal-alert--error" role="alert">
    <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
    <div><?= e($message) ?></div>
  </div>
<?php endif; ?>

<?= render_profile_form(
    $formProfile,
    $errors,
    $formConfig,
    app_url('/staff/edit.php'),
    [
        'admin' => false,
        'csrfToken' => $csrfToken,
        'cancelUrl' => app_url('/staff/workspace.php'),
        'previewUrl' => app_url('/faculty/view.php?id=' . rawurlencode((string) $profile['id'])),
        'submitLabel' => 'Save profile',
    ]
) ?>
<?php
$content = ob_get_clean();

require dirname(__DIR__, 2) . '/views/layout.php';
