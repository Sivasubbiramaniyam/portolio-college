<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Core\Enums;
use App\Core\Session;
use App\Repositories\FacultyRepositoryInterface;
use App\Repositories\UserRepositoryInterface;

/**
 * Session authentication. Credentials live in a provider behind
 * UserRepositoryInterface; the future MySQL user table can replace the JSON
 * provider without changing the HTTP layer.
 */
final class AuthService
{
    /** @param array<string, mixed> $authConfig
     *  @param array<string, mixed> $securityConfig
     *  @param array<string, mixed> $sessionConfig
     */
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly FacultyRepositoryInterface $faculty,
        private readonly array $authConfig,
        private readonly array $securityConfig,
        private readonly array $sessionConfig = []
    ) {
    }

    /**
     * @return array{user: array<string, mixed>, profile: array<string, mixed>|null}
     */
    public function login(string $email, string $password, ?string $expectedRole = null): array
    {
        $this->assertConfigured();
        $this->guardAttempts();

        $email = strtolower(trim($email));
        if ($email === '' || $password === '') {
            $this->recordFailure();
            throw ApiException::invalidCredentials();
        }

        $user = $this->users->findByEmail($email);
        if ($user === null) {
            $this->recordFailure();
            throw ApiException::invalidCredentials();
        }

        $hash = (string) ($user['passwordHash'] ?? '');
        if ($hash === '' || !password_verify($password, $hash)) {
            $this->recordFailure();
            throw ApiException::invalidCredentials();
        }

        if (($user['isActive'] ?? true) !== true) {
            $this->recordFailure();
            throw ApiException::invalidCredentials('This account is inactive.');
        }

        $role = (string) ($user['role'] ?? '');
        if (!in_array($role, Enums::ROLES, true)) {
            $this->recordFailure();
            throw ApiException::invalidCredentials();
        }

        if ($expectedRole !== null && $expectedRole !== '' && $expectedRole !== $role) {
            $this->recordFailure();
            // Deliberately generic to avoid leaking which role an account has.
            throw ApiException::invalidCredentials();
        }

        Session::regenerate();
        $sessionKey = (string) ($this->authConfig['session_key'] ?? 'auth_user_id');
        Session::set($sessionKey, (string) $user['id']);
        Session::set('auth_role', $role);
        Session::set('auth_faculty_profile_id', $user['facultyProfileId'] ?? null);
        Session::touch();
        Session::forget('_login_attempts');
        \App\Core\Csrf::rotate();

        $this->users->touchLastLogin((string) $user['id']);

        $profile = null;
        if (!empty($user['facultyProfileId'])) {
            $profile = $this->faculty->find((string) $user['facultyProfileId']);
        }

        return [
            'user' => $this->sanitize($user),
            'profile' => $profile,
        ];
    }

    public function logout(): void
    {
        Session::destroy();
    }

    /** @return array<string, mixed>|null */
    public function currentUser(): ?array
    {
        $sessionKey = (string) ($this->authConfig['session_key'] ?? 'auth_user_id');
        $userId = Session::get($sessionKey);
        if (!is_string($userId) || $userId === '') {
            return null;
        }

        $lifetime = (int) ($this->sessionConfig['lifetime'] ?? 7200);
        if (Session::expired($lifetime)) {
            $this->logout();
            return null;
        }

        $user = $this->users->findById($userId);
        if ($user === null || ($user['isActive'] ?? true) !== true) {
            $this->logout();
            return null;
        }

        Session::touch();

        return $this->sanitize($user);
    }

    /** @return array<string, mixed>|null */
    public function currentProfile(): ?array
    {
        $user = $this->currentUser();
        if ($user === null || empty($user['facultyProfileId'])) {
            return null;
        }

        return $this->faculty->find((string) $user['facultyProfileId']);
    }

    public function isConfigured(): bool
    {
        return $this->users->isConfigured();
    }

    /** @param array<string, mixed> $user
     *  @return array<string, mixed>
     */
    public function sanitize(array $user): array
    {
        return [
            'id' => (string) ($user['id'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'role' => (string) ($user['role'] ?? ''),
            'roleLabel' => Enums::roleLabel((string) ($user['role'] ?? '')),
            'facultyProfileId' => $user['facultyProfileId'] ?? null,
            'isActive' => (bool) ($user['isActive'] ?? true),
            'lastLoginAt' => $user['lastLoginAt'] ?? null,
            'createdAt' => $user['createdAt'] ?? null,
        ];
    }

    private function assertConfigured(): void
    {
        if (!$this->users->isConfigured()) {
            throw ApiException::authNotConfigured(
                'Authentication is not configured yet. Create a user with backend/bin/make-user.php.'
            );
        }
    }

    private function guardAttempts(): void
    {
        $max = (int) ($this->securityConfig['max_login_attempts'] ?? 8);
        $window = (int) ($this->securityConfig['login_window_seconds'] ?? 300);
        $attempts = Session::get('_login_attempts', ['count' => 0, 'first' => time()]);
        if (!is_array($attempts)) {
            $attempts = ['count' => 0, 'first' => time()];
        }
        if ((time() - (int) ($attempts['first'] ?? time())) > $window) {
            $attempts = ['count' => 0, 'first' => time()];
        }
        if ((int) ($attempts['count'] ?? 0) >= $max) {
            throw ApiException::tooManyRequests();
        }
        Session::set('_login_attempts', $attempts);
    }

    private function recordFailure(): void
    {
        $attempts = Session::get('_login_attempts', ['count' => 0, 'first' => time()]);
        if (!is_array($attempts)) {
            $attempts = ['count' => 0, 'first' => time()];
        }
        $attempts['count'] = (int) ($attempts['count'] ?? 0) + 1;
        if (empty($attempts['first'])) {
            $attempts['first'] = time();
        }
        Session::set('_login_attempts', $attempts);
    }
}
