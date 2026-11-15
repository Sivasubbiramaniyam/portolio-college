<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Domain exception carrying an API error code, HTTP status and field errors.
 */
final class ApiException extends RuntimeException
{
    /** @var array<string, string> */
    private array $fields;

    /** @param array<string, string> $fields */
    public function __construct(
        private readonly string $errorCode,
        string $message,
        private readonly int $httpStatus = 400,
        array $fields = []
    ) {
        parent::__construct($message);
        $this->fields = $fields;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    /** @return array<string, string> */
    public function fields(): array
    {
        return $this->fields;
    }

    /** @param array<string, string> $fields */
    public static function validation(array $fields, string $message = 'Please correct the highlighted fields.'): self
    {
        return new self('VALIDATION_ERROR', $message, 422, $fields);
    }

    public static function invalidCredentials(string $message = 'Email or password is incorrect.'): self
    {
        return new self('INVALID_CREDENTIALS', $message, 401);
    }

    public static function unauthenticated(string $message = 'Please sign in to continue.'): self
    {
        return new self('UNAUTHENTICATED', $message, 401);
    }

    public static function forbidden(string $message = 'You do not have permission to perform this action.'): self
    {
        return new self('FORBIDDEN', $message, 403);
    }

    public static function notFound(string $message = 'The requested resource was not found.'): self
    {
        return new self('NOT_FOUND', $message, 404);
    }

    public static function methodNotAllowed(string $message = 'Method not allowed.'): self
    {
        return new self('METHOD_NOT_ALLOWED', $message, 405);
    }

    public static function conflict(string $message, array $fields = []): self
    {
        return new self('CONFLICT', $message, 409, $fields);
    }

    public static function invalidFile(string $message = 'The uploaded file is not valid.'): self
    {
        return new self('INVALID_FILE', $message, 422);
    }

    public static function authNotConfigured(string $message = 'Authentication has not been configured yet.'): self
    {
        return new self('AUTH_NOT_CONFIGURED', $message, 503);
    }

    public static function csrf(string $message = 'The security token is missing or invalid. Refresh the page and try again.'): self
    {
        return new self('CSRF_TOKEN_MISMATCH', $message, 403);
    }

    public static function tooManyRequests(string $message = 'Too many attempts. Please wait a moment and try again.'): self
    {
        return new self('RATE_LIMITED', $message, 429);
    }
}
