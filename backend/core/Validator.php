<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

/**
 * Small, explicit validation helper. Field paths use the documented array
 * notation, for example qualifications[0].degree.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    public function add(string $path, string $message): void
    {
        if (!array_key_exists($path, $this->errors)) {
            $this->errors[$path] = $message;
        }
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function throwIfFails(): void
    {
        if ($this->fails()) {
            throw ApiException::validation($this->errors);
        }
    }

    /** @param array<string, mixed> $data */
    public function requiredString(array $data, string $field, string $label, int $maxLength, ?string $path = null): void
    {
        $path ??= $field;
        $value = ProfileShape::toText($data[$field] ?? '');
        if ($value === '') {
            $this->add($path, $label . ' is required.');
            return;
        }
        if (mb_strlen($value) > $maxLength) {
            $this->add($path, $label . ' must be ' . $maxLength . ' characters or fewer.');
        }
    }

    /** @param array<string, mixed> $data */
    public function optionalString(array $data, string $field, string $label, int $maxLength, ?string $path = null): void
    {
        $path ??= $field;
        $value = ProfileShape::toText($data[$field] ?? '');
        if ($value !== '' && mb_strlen($value) > $maxLength) {
            $this->add($path, $label . ' must be ' . $maxLength . ' characters or fewer.');
        }
    }

    /** @param array<string, mixed> $data
     *  @param array<int, string> $options
     */
    public function requiredEnum(array $data, string $field, string $label, array $options, ?string $path = null): void
    {
        $path ??= $field;
        $value = ProfileShape::toText($data[$field] ?? '');
        if ($value === '') {
            $this->add($path, $label . ' is required.');
            return;
        }
        if (!in_array($value, $options, true)) {
            $this->add($path, $label . ' is not a valid option.');
        }
    }

    /** @param array<string, mixed> $data
     *  @param array<int, string> $options
     */
    public function optionalEnum(array $data, string $field, string $label, array $options, ?string $path = null): void
    {
        $path ??= $field;
        $value = ProfileShape::toText($data[$field] ?? '');
        if ($value !== '' && !in_array($value, $options, true)) {
            $this->add($path, $label . ' is not a valid option.');
        }
    }

    /** @param array<string, mixed> $data */
    public function requiredEmail(array $data, string $field = 'email', ?string $path = null): void
    {
        $path ??= $field;
        $value = ProfileShape::toText($data[$field] ?? '');
        if ($value === '') {
            $this->add($path, 'Email address is required.');
            return;
        }
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->add($path, 'Enter a valid email address.');
        }
    }

    /** @param array<string, mixed> $data */
    public function requiredPhone(array $data, string $field = 'contact', ?string $path = null): void
    {
        $path ??= $field;
        $value = ProfileShape::toText($data[$field] ?? '');
        if ($value === '') {
            $this->add($path, 'Contact number is required.');
            return;
        }
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (strlen($digits) < 7 || strlen($digits) > 15) {
            $this->add($path, 'Contact number must contain between 7 and 15 digits.');
        }
    }

    /** @param array<string, mixed> $data */
    public function integerRange(array $data, string $field, string $label, int $min, ?int $max = null, bool $required = false, ?string $path = null): void
    {
        $path ??= $field;
        $raw = $data[$field] ?? '';
        $value = ProfileShape::toText($raw);
        if ($value === '') {
            if ($required) {
                $this->add($path, $label . ' is required.');
            }
            return;
        }
        if (!preg_match('/^-?\d+$/', $value)) {
            $this->add($path, $label . ' must be a whole number.');
            return;
        }
        $number = (int) $value;
        if ($number < $min || ($max !== null && $number > $max)) {
            $this->add($path, $label . ' must be between ' . $min . ' and ' . ($max ?? 'any') . '.');
        }
    }

    /** @param array<string, mixed> $data */
    public function numericMin(array $data, string $field, string $label, float $min, ?string $path = null): void
    {
        $path ??= $field;
        $value = ProfileShape::toText($data[$field] ?? '');
        if ($value === '') {
            return;
        }
        if (!is_numeric($value) || (float) $value < $min) {
            $this->add($path, $label . ' must be a number greater than or equal to ' . $min . '.');
        }
    }

    /** @param array<string, mixed> $data */
    public function date(array $data, string $field, string $label, bool $required = false, ?string $path = null): void
    {
        $path ??= $field;
        $value = ProfileShape::toText($data[$field] ?? '');
        if ($value === '') {
            if ($required) {
                $this->add($path, $label . ' is required.');
            }
            return;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            $this->add($path, $label . ' must use the YYYY-MM-DD format.');
        }
    }

    /** @param array<string, mixed> $data */
    public function month(array $data, string $field, string $label, bool $required = false, ?string $path = null): void
    {
        $path ??= $field;
        $value = ProfileShape::toText($data[$field] ?? '');
        if ($value === '') {
            if ($required) {
                $this->add($path, $label . ' is required.');
            }
            return;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m', $value);
        if ($date === false || $date->format('Y-m') !== $value) {
            $this->add($path, $label . ' must use the YYYY-MM format.');
        }
    }

    /** @param array<string, mixed> $data */
    public function url(array $data, string $field, string $label, bool $required = false, ?string $path = null): void
    {
        $path ??= $field;
        $value = ProfileShape::toText($data[$field] ?? '');
        if ($value === '') {
            if ($required) {
                $this->add($path, $label . ' is required.');
            }
            return;
        }
        if (!preg_match('#^https?://[^\s]+$#i', $value)) {
            $this->add($path, $label . ' must be a valid http or https URL.');
        }
    }

    /** @param array<string, mixed> $data */
    public function doi(array $data, string $field, string $label, ?string $path = null): void
    {
        $path ??= $field;
        $value = ProfileShape::toText($data[$field] ?? '');
        if ($value === '') {
            return;
        }
        if (preg_match('/^10\.\d{4,9}\//', $value)) {
            return;
        }
        if (!preg_match('#^https?://[^\s]+$#i', $value)) {
            $this->add($path, $label . ' must be a DOI or a valid http(s) URL.');
        }
    }

    /** @param array<string, mixed> $data */
    public function booleanish(array $data, string $field, string $label, ?string $path = null): void
    {
        $path ??= $field;
        $value = $data[$field] ?? '';
        if (is_bool($value) || is_int($value) || is_float($value)) {
            return;
        }
        $text = trim((string) $value);
        if ($text !== '' && !preg_match('/^(true|false|yes|no|on|off|1|0|checked)$/i', $text)) {
            $this->add($path, $label . ' must be a boolean value.');
        }
    }

    /** @param array<string, mixed> $data
     * @param array<string, mixed> $other
     */
    public function range(array $data, string $fromField, ?string $toField, string $label, string $format = 'Y-m-d', ?string $prefix = null): void
    {
        if ($toField === null) {
            return;
        }
        $from = ProfileShape::toText($data[$fromField] ?? '');
        $to = ProfileShape::toText($data[$toField] ?? '');
        if ($from === '' || $to === '') {
            return;
        }
        $fromDate = DateTimeImmutable::createFromFormat('!' . $format, $from);
        $toDate = DateTimeImmutable::createFromFormat('!' . $format, $to);
        if ($fromDate === false || $toDate === false) {
            return;
        }
        if ($toDate < $fromDate) {
            $path = ($prefix !== null ? $prefix . '.' : '') . $toField;
            $this->add($path, $label . ' must not be before the start date.');
        }
    }
}
