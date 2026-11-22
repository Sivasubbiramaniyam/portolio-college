<?php

declare(strict_types=1);

namespace App\Api\V1;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;

final class AdminController
{
    public function __construct(private Application $app)
    {
    }

    public function overview(Request $request): never
    {
        $service = $this->app->facultyService();
        $stats = $this->app->completionService()->directoryStats($service->all());
        $stats['totalProfiles'] = count($service->all());
        $dean = $service->dean();

        Response::json($stats, 200, [
            'deanProfileId' => $dean['id'] ?? null,
            'generatedAt' => gmdate('c'),
        ]);
    }

    public function dean(Request $request): never
    {
        $dean = $this->app->facultyService()->dean();
        if ($dean === null) {
            Response::json(null, 200, ['message' => 'No dean profile is configured.']);
        }

        Response::json($dean);
    }
}
