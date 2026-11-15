<?php

declare(strict_types=1);

/**
 * Front controller for the whole application.
 *
 * - /api/v1/*  -> JSON API (this file)
 * - everything else -> server-rendered pages (pages.php)
 *
 * Apache: see the root .htaccess.
 * PHP built-in server: backend/router.php forwards requests here.
 */

use App\Api\V1\AdminController;
use App\Api\V1\AuthController;
use App\Api\V1\ConfigController;
use App\Api\V1\FacultyController;
use App\Api\V1\UploadController;
use App\Core\ApiException;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\SecurityHeaders;
use App\Core\Session;

$requestPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');

if (preg_match('#(^|/)api/v1(/|$)#', $requestPath) !== 1) {
    require __DIR__ . '/pages.php';
    exit;
}

/** @var \App\Core\Application $app */
$app = require dirname(__DIR__) . '/bootstrap.php';

SecurityHeaders::send(true);
Session::start((array) $app->config('session', []));

$request = null;

try {
    $request = Request::fromGlobals();
    $router = new Router();

    $router->get('/health', [ConfigController::class, 'health']);
    $router->get('/bootstrap', [ConfigController::class, 'bootstrap']);

    $router->post('/auth/login', [AuthController::class, 'login'], ['csrf' => true]);
    $router->post('/auth/logout', [AuthController::class, 'logout'], ['csrf' => true]);
    $router->get('/auth/me', [AuthController::class, 'me']);

    $router->get('/faculty', [FacultyController::class, 'index']);
    $router->get('/faculty/{id}', [FacultyController::class, 'show']);
    $router->post('/faculty', [FacultyController::class, 'create'], ['auth' => true, 'roles' => ['admin']]);
    $router->put('/faculty/{id}', [FacultyController::class, 'update'], ['auth' => true, 'roles' => ['admin', 'faculty']]);
    $router->patch('/faculty/{id}', [FacultyController::class, 'patch'], ['auth' => true, 'roles' => ['admin', 'faculty']]);
    $router->delete('/faculty/{id}', [FacultyController::class, 'delete'], ['auth' => true, 'roles' => ['admin']]);
    $router->get('/faculty/{id}/completion', [FacultyController::class, 'completion']);

    $router->get('/admin/overview', [AdminController::class, 'overview'], ['auth' => true, 'roles' => ['admin']]);
    $router->get('/dean', [AdminController::class, 'dean']);

    $router->post('/uploads', [UploadController::class, 'store'], ['auth' => true, 'roles' => ['admin', 'faculty']]);

    $router->dispatch($request, $app);
} catch (ApiException $exception) {
    Logger::warning('API request rejected', [
        'code' => $exception->errorCode(),
        'status' => $exception->httpStatus(),
        'path' => $request instanceof Request ? $request->path() : '',
        'method' => $request instanceof Request ? $request->method() : '',
        'ip' => $request instanceof Request ? $request->ip() : '',
    ]);
    Response::error(
        $exception->errorCode(),
        $exception->getMessage(),
        $exception->fields(),
        $exception->httpStatus()
    );
} catch (Throwable $exception) {
    Logger::error('Unhandled API exception', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'path' => $request instanceof Request ? $request->path() : '',
        'method' => $request instanceof Request ? $request->method() : '',
    ]);
    Response::error('INTERNAL_ERROR', 'An unexpected server error occurred. Please try again later.', [], 500);
}
