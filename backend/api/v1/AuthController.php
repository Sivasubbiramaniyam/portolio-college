<?php

declare(strict_types=1);

namespace App\Api\V1;

use App\Core\ApiException;
use App\Core\Application;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class AuthController
{
    public function __construct(private readonly Application $app)
    {
    }

    public function login(Request $request): never
    {
        // `identifier` is the staff ID (staff portal) or admin ID / email
        // (admin portal). `email` is still accepted as an alias so older API
        // clients keep working.
        $identifier = trim((string) $request->input('identifier', $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $expectedRole = trim((string) $request->input('expectedRole', ''));

        $fields = [];
        if ($identifier === '') {
            $fields['identifier'] = 'Staff or admin ID is required.';
        }
        if ($password === '') {
            $fields['password'] = 'Password is required.';
        }
        if ($expectedRole !== '' && !in_array($expectedRole, ['admin', 'faculty'], true)) {
            $fields['expectedRole'] = 'Choose either the admin or faculty sign-in.';
        }
        if ($fields !== []) {
            throw ApiException::validation($fields);
        }

        try {
            $result = $this->app->authService()->login($identifier, $password, $expectedRole !== '' ? $expectedRole : null);
        } catch (ApiException $exception) {
            Logger::warning('Login failed', [
                'identifier' => $identifier,
                'ip' => $request->ip(),
                'code' => $exception->errorCode(),
            ]);
            throw $exception;
        }

        Logger::info('Login succeeded', [
            'identifier' => $identifier,
            'role' => $result['user']['role'] ?? '',
            'ip' => $request->ip(),
        ]);

        Response::json($result, 200, ['csrfToken' => Csrf::token()]);
    }

    public function logout(Request $request): never
    {
        $user = Session::get('auth_role');
        $this->app->authService()->logout();
        Logger::info('Logout', ['role' => $user]);

        Response::json(['ok' => true]);
    }

    public function me(Request $request): never
    {
        $user = $this->app->authService()->currentUser();
        $profile = null;
        if ($user !== null && !empty($user['facultyProfileId'])) {
            $profile = $this->app->facultyRepository()->find((string) $user['facultyProfileId']);
        }

        Response::json([
            'authenticated' => $user !== null,
            'user' => $user,
            'profile' => $profile,
        ], 200, ['csrfToken' => Csrf::token()]);
    }
}
