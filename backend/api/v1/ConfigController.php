<?php

declare(strict_types=1);

namespace App\Api\V1;

use App\Core\Application;
use App\Core\Enums;
use App\Core\ProfileShape;
use App\Core\Request;
use App\Core\Response;

final class ConfigController
{
    public function __construct(private readonly Application $app)
    {
    }

    public function bootstrap(Request $request): never
    {
        $institution = (array) $this->app->config('institution', []);
        $institution['logoUrl'] = (string) ($institution['logoUrl'] ?? '/frontend/assets/anna-university-logo.svg');
        $institution['defaultProfileUrl'] = (string) ($institution['defaultProfileUrl'] ?? '/frontend/assets/default-profile.svg');

        Response::json([
            'institution' => $institution,
            'departments' => Enums::DEPARTMENTS,
            'designations' => Enums::DESIGNATIONS,
            'roles' => Enums::roles(),
            'enums' => Enums::allOptions(),
            'collections' => array_keys(ProfileShape::COLLECTIONS),
            'profileRootFields' => ProfileShape::rootFields(),
            'version' => (string) $this->app->config('version', '1.0.0'),
        ], 200, ['cacheable' => true]);
    }

    public function health(Request $request): never
    {
        $checks = [
            'storage' => is_writable((string) $this->app->config('paths.storage', '')),
            'uploads' => is_writable((string) $this->app->config('paths.uploads', '')),
            'logs' => is_writable((string) $this->app->config('paths.logs', '')),
            'authConfigured' => $this->app->authService()->isConfigured(),
        ];

        $ok = $checks['storage'] && $checks['uploads'] && $checks['logs'];

        Response::json([
            'status' => $ok ? 'ok' : 'degraded',
            'version' => (string) $this->app->config('version', '1.0.0'),
            'time' => gmdate('c'),
            'checks' => $checks,
        ], $ok ? 200 : 503);
    }
}
