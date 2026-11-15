<?php

declare(strict_types=1);

use App\Core\ApiException;
use App\Core\Csrf;
use App\Core\SecurityHeaders;
use App\Core\Session;

/**
 * Staff (faculty) sign-in. Bound to the faculty role only; administrators have
 * their own sign-in page at /admin/login.php.
 *
 * @var \App\Core\Application $app
 */
$app = require dirname(__DIR__, 2) . '/bootstrap.php';

SecurityHeaders::send();
Session::start((array) $app->config('session', []));

require_once dirname(__DIR__, 2) . '/views/helpers.php';
require_once dirname(__DIR__, 2) . '/views/login_form.php';

$expectedRole = 'faculty';

$user = $app->authService()->currentUser();
if (is_array($user)) {
    redirect_to((string) ($user['role'] ?? '') === 'admin' ? '/admin/dashboard.php' : '/staff/workspace.php');
}

$csrfToken = Csrf::token();
$errors = [];
$message = '';
$notice = '';
$email = '';
$authConfigured = $app->authService()->isConfigured();
$signedOut = isset($_GET['signedout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!Csrf::check(is_string($_POST['_csrf'] ?? null) ? (string) $_POST['_csrf'] : null)) {
        $message = 'The sign-in form expired. Refresh the page and try again.';
    } elseif ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address.';
    } elseif ($password === '') {
        $errors['password'] = 'Password is required.';
    } else {
        try {
            $app->authService()->login($email, $password, $expectedRole);
            Session::flash('flash', [
                'type' => 'success',
                'message' => 'Welcome back. You are signed in.',
            ]);
            redirect_to('/staff/workspace.php');
        } catch (ApiException $exception) {
            if ($exception->errorCode() === 'AUTH_NOT_CONFIGURED') {
                $notice = 'No staff accounts exist yet. An administrator must create one with <code>php backend/bin/make-user.php</code>.';
            } elseif ($exception->errorCode() === 'RATE_LIMITED') {
                $message = $exception->getMessage();
            } elseif ($exception->errorCode() === 'VALIDATION_ERROR') {
                $errors = $exception->fields();
                $message = $exception->getMessage();
            } else {
                $message = 'Email or password is incorrect.';
            }
        }
    }
}

$pageTitle = 'Staff sign in';
$pageHeading = 'Staff sign in';
$activeNav = '';
$useSidebar = false;
$bodyClass = 'portal-login-page';
$flash = pull_flash();
$pageActions = '';

$content = render_login_panel([
    'action' => app_url('/staff/login.php'),
    'csrfToken' => $csrfToken,
    'eyebrow' => 'Staff access',
    'title' => 'Staff sign in',
    'intro' => 'Sign in with your faculty account to maintain your own profile.',
    'email' => $email,
    'errors' => $errors,
    'message' => $message,
    'notice' => $notice,
    'signedOut' => $signedOut,
    'authConfigured' => $authConfigured,
    'altLoginUrl' => app_url('/admin/login.php'),
    'altLoginLabel' => 'Administrator',
]);

require dirname(__DIR__, 2) . '/views/layout.php';
