<?php

declare(strict_types=1);

use App\Core\ApiException;
use App\Core\Csrf;
use App\Core\SecurityHeaders;
use App\Core\Session;

/**
 * Staff self-registration. Gated by the validuser whitelist: an account can
 * only be created for a Staff ID that an administrator has approved. The
 * display name is taken from the whitelist, never entered here.
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
$values = ['identifier' => '', 'email' => '', 'department' => ''];
$registrationOpen = $app->whitelistRepository()->allStaff() !== [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['identifier'] = trim((string) ($_POST['identifier'] ?? ''));
    $values['email'] = trim((string) ($_POST['email'] ?? ''));
    $values['department'] = trim((string) ($_POST['department'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirmPassword'] ?? '');

    if (!Csrf::check(is_string($_POST['_csrf'] ?? null) ? (string) $_POST['_csrf'] : null)) {
        $message = 'The registration form expired. Refresh the page and try again.';
    } else {
        try {
            $app->registrationService()->registerStaff(
                $values['identifier'],
                $values['email'],
                $password,
                $confirmPassword,
                $values['department']
            );
            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Account created. Sign in with your Staff ID.',
            ]);
            redirect_to('/staff/login.php');
        } catch (ApiException $exception) {
            if ($exception->errorCode() === 'VALIDATION_ERROR') {
                $errors = $exception->fields();
                if (isset($errors['staffId'])) {
                    $errors['identifier'] = $errors['staffId'];
                    unset($errors['staffId']);
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

$pageTitle = 'Staff registration';
$pageHeading = 'Staff registration';
$activeNav = '';
$useSidebar = false;
$bodyClass = 'portal-login-page portal-register-page';
$flash = pull_flash();
$pageActions = '';

$content = render_register_panel([
    'action' => app_url('/staff/register.php'),
    'csrfToken' => $csrfToken,
    'role' => 'staff',
    'eyebrow' => 'Staff registration',
    'title' => 'Create your staff account',
    'identifierLabel' => 'Staff ID',
    'identifierPlaceholder' => 'Enter your Staff ID',
    'values' => $values,
    'errors' => $errors,
    'message' => $message,
    'notice' => $notice,
    'authConfigured' => $registrationOpen,
    'loginUrl' => app_url('/staff/login.php'),
    'loginLabel' => 'Sign in',
]);

require dirname(__DIR__, 2) . '/views/layout.php';
