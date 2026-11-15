<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Pre-approved identity boundary for self-registration.
 *
 * A staff account may only be created for a staff ID that exists in the
 * validuser whitelist; an administrator account only for an admin ID in
 * validadmin. Mirrors the production auth tables of the same name.
 */
interface WhitelistRepositoryInterface
{
    public function isConfigured(): bool;

    /** @return array<int, array<string, mixed>> */
    public function allStaff(): array;

    /** @return array<int, array<string, mixed>> */
    public function allAdmins(): array;

    /** @return array<string, mixed>|null */
    public function findStaff(string $staffId): ?array;

    /** @return array<string, mixed>|null */
    public function findAdmin(string $adminId): ?array;
}
