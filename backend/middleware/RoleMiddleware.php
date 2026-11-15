<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ApiException;
use App\Core\Request;

final class RoleMiddleware
{
    /** @param array<int, string> $roles
     *  @param array<string, mixed>|null $user
     */
    public static function authorize(Request $request, array $roles, ?array $user = null): void
    {
        $user ??= $request->user();
        if ($user === null) {
            throw ApiException::unauthenticated();
        }

        $role = (string) ($user['role'] ?? '');
        if (!in_array($role, $roles, true)) {
            throw ApiException::forbidden();
        }
    }

    /** @param array<string, mixed>|null $user */
    public static function requireAdmin(Request $request, ?array $user = null): void
    {
        self::authorize($request, ['admin'], $user);
    }
}
