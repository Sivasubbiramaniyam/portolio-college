<?php

declare(strict_types=1);

namespace App\Api\V1;

use App\Core\ApiException;
use App\Core\Application;
use App\Core\Request;
use App\Core\Response;

final class UploadController
{
    public function __construct(private readonly Application $app)
    {
    }

    public function store(Request $request): never
    {
        $user = $request->user();
        if ($user === null) {
            throw ApiException::unauthenticated();
        }

        $file = $request->file('file');
        if ($file === null) {
            throw ApiException::invalidFile('Choose a file to upload.');
        }

        $kind = trim((string) ($request->input('kind') ?? $request->query('kind', 'document')));
        $ownerProfileId = trim((string) ($request->input('profileId') ?? $request->query('profileId', '')));

        if ($user['role'] === 'faculty') {
            $ownerProfileId = (string) ($user['facultyProfileId'] ?? '');
            if ($ownerProfileId === '') {
                throw ApiException::forbidden('This account is not linked to a faculty profile.');
            }
        } elseif ($user['role'] === 'admin') {
            if ($ownerProfileId !== '') {
                // Ensure the referenced profile exists before linking the asset.
                $this->app->facultyService()->require($ownerProfileId);
            } else {
                $ownerProfileId = null;
            }
        } else {
            throw ApiException::forbidden();
        }

        $asset = $this->app->uploadService()->store($file, $kind, $ownerProfileId, (string) ($user['id'] ?? ''));
        Response::json($asset, 201);
    }
}
