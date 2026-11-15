<?php

declare(strict_types=1);

namespace App\Repositories;

use PDOException;

/**
 * Shared PDO error translation for the MySQL repositories.
 */
trait TranslatesDriverErrors
{
    /**
     * True when the driver rejected a write because a UNIQUE / PRIMARY KEY
     * constraint was violated (SQLSTATE 23000, MySQL error 1062).
     */
    protected static function isDuplicateKey(PDOException $exception): bool
    {
        return (string) $exception->getCode() === '23000'
            || (int) ($exception->errorInfo[1] ?? 0) === 1062;
    }
}
