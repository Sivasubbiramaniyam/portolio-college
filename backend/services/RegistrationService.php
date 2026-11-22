<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\WhitelistRepositoryInterface;

/**
 * Self-service account creation, gated by the validuser / validadmin
 * whitelists. Mirrors the production registration rules:
 *
 *   * staff: the staff ID must be pre-approved; the display name is taken from
 *     the whitelist (never chosen by the registrant); password 8–16 characters.
 *   * admin: the admin ID must be pre-approved; name, email and password are
 *     supplied by the registrant; password at least 8 characters.
 *
 * Email is required for both (it is the contact address and the administrator
 * fallback login). When a staff ID matches a published faculty profile, the
 * account is linked to it so the workspace resolves immediately.
 */
final class RegistrationService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private WhitelistRepositoryInterface $whitelist,
        private FacultyService $faculty
    ) {
    }

    /**
     * @return array{user: array<string, mixed>, profile: array<string, mixed>|null}
     */
    public function registerStaff(
        string $staffId,
        string $email,
        string $password,
        string $confirmPassword,
        string $department = ''
    ): array {
        $staffId = trim($staffId);
        $email = strtolower(trim($email));

        $fields = $this->validateBasics($staffId, $email, $password, $confirmPassword, 'staffId');
        if ($fields !== []) {
            throw ApiException::validation($fields);
        }

        if (strlen($password) < 8 || strlen($password) > 16) {
            throw ApiException::validation(['password' => 'Password must be between 8 and 16 characters long.']);
        }

        $entry = $this->whitelist->findStaff($staffId);
        if ($entry === null) {
            throw ApiException::validation(['staffId' => 'Invalid Staff ID. Account creation denied.']);
        }

        if ($this->users->findByStaffId($staffId) !== null) {
            throw ApiException::validation(['staffId' => 'An account with this Staff ID already exists.']);
        }
        if ($this->users->findByEmail($email) !== null) {
            throw ApiException::validation(['email' => 'Email already registered for another account.']);
        }

        $profile = $this->profileForStaffId($staffId);
        $department = trim($department);
        if ($department === '' && $profile !== null) {
            $department = trim((string) ($profile['department'] ?? ''));
        }

        $hash = $this->hash($password);

        $user = $this->users->create([
            'id' => 'usr-' . bin2hex(random_bytes(6)),
            'email' => $email,
            'name' => (string) ($entry['name'] ?? ''),
            'staffId' => $staffId,
            'adminId' => null,
            'department' => $department,
            'role' => 'faculty',
            'facultyProfileId' => $profile !== null ? (string) $profile['id'] : null,
            'passwordHash' => $hash,
            'isActive' => true,
            'createdAt' => gmdate('c'),
            'lastLoginAt' => null,
        ]);

        return ['user' => $user, 'profile' => $profile];
    }

    /**
     * @return array{user: array<string, mixed>, profile: null}
     */
    public function registerAdmin(
        string $adminId,
        string $name,
        string $email,
        string $password,
        string $confirmPassword
    ): array {
        $adminId = trim($adminId);
        $name = trim($name);
        $email = strtolower(trim($email));

        $fields = $this->validateBasics($adminId, $email, $password, $confirmPassword, 'adminId');
        if ($name === '') {
            $fields['name'] = 'Full name is required.';
        }
        if ($fields !== []) {
            throw ApiException::validation($fields);
        }

        if (strlen($password) < 8) {
            throw ApiException::validation(['password' => 'Password must be at least 8 characters long.']);
        }

        $entry = $this->whitelist->findAdmin($adminId);
        if ($entry === null) {
            throw ApiException::validation(['adminId' => 'Invalid Admin ID. Account creation denied.']);
        }

        if ($this->users->findByAdminId($adminId) !== null) {
            throw ApiException::validation(['adminId' => 'An account with this Admin ID already exists.']);
        }
        if ($this->users->findByEmail($email) !== null) {
            throw ApiException::validation(['email' => 'Email already registered for another account.']);
        }

        $hash = $this->hash($password);

        $user = $this->users->create([
            'id' => 'usr-' . bin2hex(random_bytes(6)),
            'email' => $email,
            'name' => $name,
            'staffId' => null,
            'adminId' => $adminId,
            'department' => '',
            'role' => 'admin',
            'facultyProfileId' => null,
            'passwordHash' => $hash,
            'isActive' => true,
            'createdAt' => gmdate('c'),
            'lastLoginAt' => null,
        ]);

        return ['user' => $user, 'profile' => null];
    }

    /** @return array<string, string> */
    private function validateBasics(
        string $identifier,
        string $email,
        string $password,
        string $confirmPassword,
        string $idField
    ): array {
        $fields = [];
        if ($identifier === '') {
            $fields[$idField] = 'This field is required.';
        }
        if ($email === '') {
            $fields['email'] = 'Email address is required.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $fields['email'] = 'Enter a valid email address.';
        }
        if ($password === '') {
            $fields['password'] = 'Password is required.';
        }
        if ($password !== $confirmPassword) {
            $fields['confirmPassword'] = 'Passwords do not match.';
        }

        return $fields;
    }

    /** @return array<string, mixed>|null */
    private function profileForStaffId(string $staffId): ?array
    {
        foreach ($this->faculty->all() as $profile) {
            if (trim((string) ($profile['facultyId'] ?? '')) === $staffId) {
                return $profile;
            }
        }

        return null;
    }

    private function hash(string $password): string
    {
        $algorithm = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        $hash = password_hash($password, $algorithm);
        if (!is_string($hash) || $hash === '') {
            throw new ApiException('SERVER_ERROR', 'The password could not be hashed.', 500);
        }

        return $hash;
    }
}
