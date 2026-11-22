<?php

declare(strict_types=1);

use App\Core\ApiException;
use App\Core\Csrf;
use App\Core\SecurityHeaders;
use App\Core\Session;

/**
 * Administrator self-registration. Gated by the validadmin whitelist: an
 * account can only be created for an Admin ID an existing administrator has
 * approved.
 *
 * @var \App\Core\Application $app
 */
$app = require dirname(__DIR__, 2) . '/bootstrap.php';

SecurityHeaders::send();
Session::start((array) $app->config('session', []));

require_once dirname(__DIR__, 2) . '/views/helpers.php';
require_once dirname(__DIR__, 2) . '/views/register_form.php';

$user = $app->authService()->currentUser();
if (is_array($user)) {
    redirect_to((string) ($user['role'] ?? '') === 'admin' ? '/admin/dashboard.php' : '/staff/workspace.php');
}

$csrfToken = Csrf::token();
$errors = [];
$message = '';
$notice = '';
$values = ['identifier' => '', 'name' => '', 'email' => ''];
$registrationOpen = $app->whitelistRepository()->allAdmins() !== [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['identifier'] = trim((string) ($_POST['identifier'] ?? ''));
    $values['name'] = trim((string) ($_POST['name'] ?? ''));
    $values['email'] = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirmPassword'] ?? '');

    if (!Csrf::check(is_string($_POST['_csrf'] ?? null) ? (string) $_POST['_csrf'] : null)) {
        $message = 'The registration form expired. Refresh the page and try again.';
    } else {
        try {
            $app->registrationService()->registerAdmin(
                $values['identifier'],
                $values['name'],
                $values['email'],
                $password,
                $confirmPassword
            );
            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Administrator account created. Sign in with your Admin ID.',
            ]);
            redirect_to('/admin/login.php');
        } catch (ApiException $exception) {
            if ($exception->errorCode() === 'VALIDATION_ERROR') {
                $errors = $exception->fields();
                if (isset($errors['adminId'])) {
                    $errors['identifier'] = $errors['adminId'];
                    unset($errors['adminId']);
                }
                $message = $exception->getMessage();
            } elseif ($exception->errorCode() === 'CONFLICT') {
                $message = $exception->getMessage();
            } else {
                $message = 'Registration could not be completed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Administrator registration';
$pageHeading = 'Administrator registration';
$activeNav = '';
$useSidebar = false;
$bodyClass = 'portal-login-page portal-register-page';
$flash = pull_flash();
$pageActions = '';

$content = render_register_panel([
    'action' => app_url('/admin/register.php'),
    'csrfToken' => $csrfToken,
    'role' => 'admin',
    'eyebrow' => 'Administrator registration',
    'title' => 'Create an administrator account',
    'intro' => 'Register with the Admin ID an existing administrator has approved for you.',
    'identifierLabel' => 'Admin ID',
    'identifierPlaceholder' => 'Your approved admin ID',
    'identifierHint' => 'The admin ID recorded in the administrator whitelist.',
    'values' => $values,
    'errors' => $errors,
    'message' => $message,
    'notice' => $notice,
    'authConfigured' => $registrationOpen,
    'loginUrl' => app_url('/admin/login.php'),
    'loginLabel' => 'Sign in',
]);

require dirname(__DIR__, 2) . '/views/layout.php';
