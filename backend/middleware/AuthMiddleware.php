<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ApiException;
use App\Core\Application;
use App\Core\Logger;
use App\Core\Request;

final class AuthMiddleware
{
    /** @return array<string, mixed> */
    public static function authenticate(Request $request, Application $app): array
    {
        $user = $app->authService()->currentUser();
        if ($user === null) {
            throw ApiException::unauthenticated();
        }

        $request->setUser($user);

        return $user;
    }

    public static function optional(Request $request, Application $app): ?array
    {
        $user = $app->authService()->currentUser();
        if ($user !== null) {
            $request->setUser($user);
        }

        return $user;
    }

    /** @param array<string, mixed> $user */
    public static function logAccess(string $action, array $user, string $target = ''): void
    {
        Logger::info('Authorised action', [
            'action' => $action,
            'role' => $user['role'] ?? '',
            'userId' => $user['id'] ?? '',
            'target' => $target,
        ]);
    }
}
