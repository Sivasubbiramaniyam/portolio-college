<?php

declare(strict_types=1);

use App\Core\ApiException;
use App\Core\Csrf;
use App\Core\SecurityHeaders;
use App\Core\Session;

/**
 * Administrator sign-in. Bound to the admin role only; staff have their own
 * sign-in page at /staff/login.php.
 *
 * @var \App\Core\Application $app
 */
$app = require dirname(__DIR__, 2) . '/bootstrap.php';

SecurityHeaders::send();
Session::start((array) $app->config('session', []));

require_once dirname(__DIR__, 2) . '/views/helpers.php';
require_once dirname(__DIR__, 2) . '/views/login_form.php';

$expectedRole = 'admin';

$user = $app->authService()->currentUser();
if (is_array($user)) {
    redirect_to((string) ($user['role'] ?? '') === 'admin' ? '/admin/dashboard.php' : '/staff/workspace.php');
}

$csrfToken = Csrf::token();
$errors = [];
$message = '';
$notice = '';
$identifier = '';
$authConfigured = $app->authService()->isConfigured();
$signedOut = isset($_GET['signedout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim((string) ($_POST['identifier'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!Csrf::check(is_string($_POST['_csrf'] ?? null) ? (string) $_POST['_csrf'] : null)) {
        $message = 'The sign-in form expired. Refresh the page and try again.';
    } elseif ($identifier === '') {
        $errors['identifier'] = 'Admin ID is required.';
    } elseif ($password === '') {
        $errors['password'] = 'Password is required.';
    } else {
        try {
            $app->authService()->login($identifier, $password, $expectedRole);
            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Welcome back. You are signed in as an administrator.',
            ]);
            redirect_to('/admin/dashboard.php');
        } catch (ApiException $exception) {
            if ($exception->errorCode() === 'AUTH_NOT_CONFIGURED') {
                $notice = 'No accounts exist yet. Register with your Admin ID, or create one with <code>php backend/bin/make-user.php</code>.';
            } elseif ($exception->errorCode() === 'RATE_LIMITED') {
                $message = $exception->getMessage();
            } elseif ($exception->errorCode() === 'VALIDATION_ERROR') {
                $errors = $exception->fields();
                $message = $exception->getMessage();
            } else {
                $message = 'Admin ID or password is incorrect.';
            }
        }
    }
}

$pageTitle = 'Administrator sign in';
$pageHeading = 'Administrator sign in';
$activeNav = '';
$useSidebar = false;
$bodyClass = 'portal-login-page';
$flash = pull_flash();
$pageActions = '';

$content = render_login_panel([
    'action' => app_url('/admin/login.php'),
    'csrfToken' => $csrfToken,
    'eyebrow' => 'Administrator access',
    'title' => 'Administrator sign in',
    'intro' => 'Sign in with an administrator account to manage every profile.',
    'identifier' => $identifier,
    'identifierLabel' => 'Admin ID',
    'identifierPlaceholder' => 'Admin ID or email',
    'identifierHint' => 'Your admin ID, or the email address on the account.',
    'errors' => $errors,
    'message' => $message,
    'notice' => $notice,
    'signedOut' => $signedOut,
    'authConfigured' => $authConfigured,
    'altLoginUrl' => app_url('/staff/login.php'),
    'altLoginLabel' => 'Staff',
    'registerUrl' => app_url('/admin/register.php'),
    'registerLabel' => 'Register with your Admin ID',
]);

require dirname(__DIR__, 2) . '/views/layout.php';
