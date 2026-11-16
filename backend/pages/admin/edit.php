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
    redirect_to('/admin/login.php');
}
if ((string) ($user['role'] ?? '') === 'faculty') {
    redirect_to('/staff/workspace.php');
}
if ((string) ($user['role'] ?? '') !== 'admin') {
    redirect_to('/');
}

/** @var array<string, mixed> $formConfig */
$formConfig = require dirname(__DIR__, 2) . '/views/form_config.php';

$facultyService = $app->facultyService();
$csrfToken = Csrf::token();

$id = trim((string) ($_POST['id'] ?? $_GET['id'] ?? ''));
$existing = $id !== '' ? $facultyService->maybeFind($id) : null;

if ($id !== '' && $existing === null) {
    Session::flash('flash', ['type' => 'error', 'message' => 'That faculty profile does not exist.']);
    redirect_to('/admin/dashboard.php');
}

$errors = [];
$message = '';
$status = 200;
$isCreate = $existing === null;

$allProfiles = $facultyService->all();
$suggestedOrder = 0;
foreach ($allProfiles as $candidate) {
    $suggestedOrder = max($suggestedOrder, (int) ($candidate['displayOrder'] ?? 0));
}

$formProfile = $existing ?? ProfileShape::emptyProfile();
if ($isCreate) {
    $formProfile['displayOrder'] = $suggestedOrder + 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::check(is_string($_POST['_csrf'] ?? null) ? (string) $_POST['_csrf'] : null)) {
        $status = 419;
        $message = 'Your session expired while the form was open. Refresh the page and try again.';
    } elseif ((string) ($_POST['action'] ?? '') === 'delete') {
        if ($existing === null) {
            redirect_to('/admin/dashboard.php');
        }
        try {
            $facultyService->delete((string) $existing['id'], $user);
            Session::flash('flash', ['type' => 'success', 'message' => 'The faculty profile was deleted.']);
        } catch (ApiException $exception) {
            Session::flash('flash', ['type' => 'error', 'message' => $exception->getMessage()]);
        } catch (Throwable) {
            Session::flash('flash', ['type' => 'error', 'message' => 'The profile could not be deleted because of a server error.']);
        }
        redirect_to('/admin/dashboard.php');
    } else {
        $input = collect_profile_form_input($_POST, $formConfig, $existing ?? [], true);
        if ($existing !== null) {
            $input['id'] = (string) $existing['id'];
        }

        foreach (['photo', 'banner'] as $media) {
            $fileKey = $media . '_file';
            $file = $_FILES[$fileKey] ?? null;
            if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            try {
                $asset = $app->uploadService()->store($file, $media, $existing['id'] ?? null, (string) ($user['id'] ?? ''));
                $input[$media] = (string) ($asset['url'] ?? '');
            } catch (ApiException $exception) {
                $errors[$fileKey] = $exception->getMessage();
            } catch (Throwable) {
                $errors[$fileKey] = 'The image could not be stored. Please try a smaller JPEG, PNG or WebP file.';
            }
        }

        if ($errors === []) {
            try {
                if ($existing !== null) {
                    $saved = $facultyService->update((string) $existing['id'], $input, false, $user);
                    Session::flash('flash', [
                        'type' => 'success',
                        'message' => 'The profile for ' . (string) ($saved['name'] ?? 'the faculty member') . ' was updated.',
                    ]);
                    redirect_to('/admin/edit.php?id=' . rawurlencode((string) $saved['id']));
                }

                $created = $facultyService->create($input, $user);
                Session::flash('flash', [
                    'type' => 'success',
                    'message' => 'The profile for ' . (string) ($created['name'] ?? 'the faculty member') . ' was created.',
                ]);
                redirect_to('/admin/edit.php?id=' . rawurlencode((string) $created['id']));
            } catch (ApiException $exception) {
                if ($exception->errorCode() === 'VALIDATION_ERROR' || $exception->errorCode() === 'CONFLICT') {
                    $errors = array_merge($errors, $exception->fields());
                    $status = $exception->httpStatus();
                    $message = $exception->getMessage();
                } else {
                    $status = $exception->httpStatus();
                    $message = $exception->getMessage();
                }
            } catch (Throwable) {
                $status = 500;
                $message = 'The profile could not be saved because of a server error. Please try again.';
            }
        } else {
            $status = 422;
            $message = 'The profile could not be saved. Please check the highlighted upload fields.';
        }

        if (is_array($input)) {
            $base = $existing ?? ProfileShape::emptyProfile();
            $formProfile = ProfileShape::normalize(array_merge($base, $input));
            if ($existing !== null) {
                $formProfile['id'] = (string) $existing['id'];
                $formProfile['createdAt'] = (string) ($existing['createdAt'] ?? '');
                $formProfile['updatedAt'] = (string) ($existing['updatedAt'] ?? '');
            }
            if (!$isCreate) {
                $formProfile['displayOrder'] = (int) ($existing['displayOrder'] ?? $formProfile['displayOrder']);
            }
        }
    }
}

if ($status !== 200) {
    http_response_code($status);
}

$flash = pull_flash();
$sidebarLinks = [
    ['key' => 'overview', 'label' => 'Admin Dashboard', 'href' => app_url('/admin/dashboard.php'), 'icon' => 'speedometer2'],
    ['key' => 'create', 'label' => 'Create Profile', 'href' => app_url('/admin/edit.php'), 'icon' => 'person-plus'],
];

$pageTitle = $isCreate ? 'Create faculty profile' : 'Edit faculty profile';
$pageHeading = $isCreate ? 'Create faculty profile' : 'Edit faculty profile';
$activeNav = $isCreate ? 'create' : 'overview';
$useSidebar = true;

$isDean = $existing !== null && (($existing['isDean'] ?? false) === true);
ob_start();
?>
<nav class="view-bar reveal" aria-label="Editor navigation">
  <div>
    <a class="btn btn-ghost" href="<?= e(app_url('/admin/dashboard.php')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to dashboard</a>
  </div>
  <div class="section-heading__actions">
    <?php if ($existing !== null): ?>
      <a class="btn btn-soft" href="<?= e(app_url('/faculty/view.php?id=' . rawurlencode((string) $existing['id']))) ?>"><i class="bi bi-eye" aria-hidden="true"></i> View public profile</a>
    <?php endif; ?>
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
    $existing !== null
        ? app_url('/admin/edit.php?id=' . rawurlencode((string) $existing['id']))
        : app_url('/admin/edit.php'),
    [
        'admin' => true,
        'csrfToken' => $csrfToken,
        'cancelUrl' => app_url('/admin/dashboard.php'),
        'previewUrl' => $existing !== null ? app_url('/faculty/view.php?id=' . rawurlencode((string) $existing['id'])) : '',
        'submitLabel' => $isCreate ? 'Create profile' : 'Save changes',
    ]
) ?>

<?php if ($existing !== null): ?>
  <section class="danger-zone reveal" aria-labelledby="danger-title">
    <h2 id="danger-title">Danger zone</h2>
    <?php if ($isDean): ?>
      <p>The dean profile is protected and cannot be deleted. You can still update every field, including marking another profile as the dean.</p>
    <?php else: ?>
      <p>Deleting a profile removes it from the directory and cannot be undone. Linked user accounts keep their session but lose access to this profile.</p>
      <form method="post" action="<?= e(app_url('/admin/edit.php?id=' . rawurlencode((string) $existing['id']))) ?>" data-confirm="Delete this profile permanently? This cannot be undone.">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= e((string) $existing['id']) ?>">
        <button class="btn btn-danger" type="submit"><i class="bi bi-trash3" aria-hidden="true"></i> Delete profile permanently</button>
      </form>
    <?php endif; ?>
  </section>
<?php endif; ?>
<?php
$content = ob_get_clean();

require dirname(__DIR__, 2) . '/views/layout.php';
