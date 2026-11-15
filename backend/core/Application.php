<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Database;
use App\Repositories\AssetRepositoryInterface;
use App\Repositories\FacultyRepositoryInterface;
use App\Repositories\JsonAssetRepository;
use App\Repositories\JsonFacultyRepository;
use App\Repositories\JsonUserRepository;
use App\Repositories\MysqlAssetRepository;
use App\Repositories\MysqlFacultyRepository;
use App\Repositories\MysqlUserRepository;
use App\Repositories\UserRepositoryInterface;
use App\Services\AuthService;
use App\Services\FacultyService;
use App\Services\ProfileCompletionService;
use App\Services\UploadService;

/**
 * Lightweight service container. Deliberately explicit: no framework magic and
 * no hidden global state beyond the immutable config.
 */
final class Application
{
    /** @var array<string, mixed> */
    private array $instances = [];

    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
        foreach (['storage', 'uploads', 'logs'] as $pathKey) {
            $path = (string) ($config['paths'][$pathKey] ?? '');
            if ($path !== '' && !is_dir($path)) {
                @mkdir($path, 0775, true);
            }
        }

        Logger::init((string) ($config['paths']['logs'] ?? dirname(__DIR__) . '/logs') . DIRECTORY_SEPARATOR . 'app.log');
    }

    public function config(string $key, mixed $default = null): mixed
    {
        $value = $this->config;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function database(): Database
    {
        return $this->make('database', fn (): Database => new Database((array) $this->config('database', [])));
    }

    public function facultyRepository(): FacultyRepositoryInterface
    {
        return $this->make('faculty_repository', function (): FacultyRepositoryInterface {
            if (strtolower((string) $this->config('repository.driver', 'mysql')) === 'mysql') {
                return new MysqlFacultyRepository($this->database());
            }

            return new JsonFacultyRepository(
                (string) $this->config('paths.faculty_file'),
                (string) $this->config('paths.faculty_seed')
            );
        });
    }

    public function userRepository(): UserRepositoryInterface
    {
        return $this->make('user_repository', function (): UserRepositoryInterface {
            if (strtolower((string) $this->config('auth.user_provider', 'mysql')) === 'mysql') {
                return new MysqlUserRepository($this->database());
            }

            return new JsonUserRepository((string) $this->config('paths.users_file'));
        });
    }

    public function assetRepository(): AssetRepositoryInterface
    {
        return $this->make('asset_repository', function (): AssetRepositoryInterface {
            if (strtolower((string) $this->config('repository.driver', 'mysql')) === 'mysql') {
                return new MysqlAssetRepository($this->database());
            }

            return new JsonAssetRepository((string) $this->config('paths.assets_file'));
        });
    }

    public function completionService(): ProfileCompletionService
    {
        return $this->make('completion_service', static fn (): ProfileCompletionService => new ProfileCompletionService());
    }

    public function facultyService(): FacultyService
    {
        return $this->make('faculty_service', function (): FacultyService {
            return new FacultyService(
                $this->facultyRepository(),
                $this->completionService(),
                (string) $this->config('institution.deanId', 'fac-dean')
            );
        });
    }

    public function authService(): AuthService
    {
        return $this->make('auth_service', function (): AuthService {
            return new AuthService(
                $this->userRepository(),
                $this->facultyRepository(),
                $this->config('auth', []),
                $this->config('security', []),
                $this->config('session', [])
            );
        });
    }

    public function uploadService(): UploadService
    {
        return $this->make('upload_service', function (): UploadService {
            return new UploadService(
                $this->assetRepository(),
                (string) $this->config('paths.uploads'),
                (array) $this->config('uploads', [])
            );
        });
    }

    public function logger(): void
    {
    }

    /** @template T
     * @param callable(): T $factory
     * @return T
     */
    private function make(string $key, callable $factory): mixed
    {
        if (!array_key_exists($key, $this->instances)) {
            $this->instances[$key] = $factory();
        }

        return $this->instances[$key];
    }
}
