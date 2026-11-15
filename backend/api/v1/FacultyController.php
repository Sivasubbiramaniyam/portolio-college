<?php

declare(strict_types=1);

namespace App\Api\V1;

use App\Core\ApiException;
use App\Core\Application;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;

final class FacultyController
{
    public function __construct(private readonly Application $app)
    {
    }

    public function index(Request $request): never
    {
        $filters = [
            'q' => (string) $request->query('q', ''),
            'department' => (string) $request->query('department', ''),
            'designation' => (string) $request->query('designation', ''),
            'includeDean' => (string) $request->query('includeDean', ''),
            'page' => (int) $request->query('page', 1),
            'pageSize' => (int) $request->query('pageSize', 50),
        ];

        $service = $this->app->facultyService();
        $result = $service->list($filters);
        $stats = $this->app->completionService()->directoryStats($service->all());

        Response::json($result['items'], 200, [
            'total' => $result['total'],
            'page' => $result['page'],
            'pageSize' => $result['pageSize'],
            'stats' => $stats,
        ]);
    }

    /** @param array<string, string> $params */
    public function show(Request $request, array $params): never
    {
        $profile = $this->app->facultyService()->require((string) ($params['id'] ?? ''));
        $completion = $this->app->completionService()->calculate($profile);

        Response::json($profile, 200, ['completion' => $completion]);
    }

    public function create(Request $request): never
    {
        $actor = $request->user() ?? [];
        $profile = $this->app->facultyService()->create($request->all(), $actor);
        Logger::info('Faculty profile created', [
            'profileId' => $profile['id'] ?? '',
            'actorId' => $actor['id'] ?? '',
        ]);

        Response::json($profile, 201);
    }

    /** @param array<string, string> $params */
    public function update(Request $request, array $params): never
    {
        $actor = $request->user() ?? [];
        $id = (string) ($params['id'] ?? '');
        $profile = $this->app->facultyService()->update($id, $request->all(), false, $actor);
        Logger::info('Faculty profile updated', [
            'profileId' => $id,
            'actorId' => $actor['id'] ?? '',
            'method' => 'PUT',
        ]);

        Response::json($profile);
    }

    /** @param array<string, string> $params */
    public function patch(Request $request, array $params): never
    {
        $actor = $request->user() ?? [];
        $id = (string) ($params['id'] ?? '');
        $profile = $this->app->facultyService()->update($id, $request->all(), true, $actor);
        Logger::info('Faculty profile patched', [
            'profileId' => $id,
            'actorId' => $actor['id'] ?? '',
            'method' => 'PATCH',
        ]);

        Response::json($profile);
    }

    /** @param array<string, string> $params */
    public function delete(Request $request, array $params): never
    {
        $actor = $request->user() ?? [];
        $id = (string) ($params['id'] ?? '');
        $this->app->facultyService()->delete($id, $actor);
        Logger::info('Faculty profile deleted', [
            'profileId' => $id,
            'actorId' => $actor['id'] ?? '',
        ]);

        Response::json(['id' => $id, 'deleted' => true]);
    }

    /** @param array<string, string> $params */
    public function completion(Request $request, array $params): never
    {
        $id = (string) ($params['id'] ?? '');
        if ($id === '') {
            throw ApiException::notFound('The faculty profile was not found.');
        }
        Response::json($this->app->facultyService()->completionFor($id));
    }
}
