<?php

declare(strict_types=1);

namespace App\Domain\Shared\Validation;

use InvalidArgumentException;

/**
 * Input validation for financial operations.
 *
 * Provides consistent validation across all shared interfaces
 * to prevent injection attacks and ensure data integrity.
 */
trait FinancialInputValidator
{
    /**
     * Validate UUID format.
     *
     * @param string $value The value to validate
     * @param string $fieldName Name of the field for error messages
     * @throws InvalidArgumentException If validation fails
     */
    protected function validateUuid(string $value, string $fieldName = 'ID'): void
    {
        if (empty($value)) {
            throw new InvalidArgumentException("{$fieldName} cannot be empty");
        }

        // UUID v4 pattern
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

        if (! preg_match($pattern, $value)) {
            throw new InvalidArgumentException("{$fieldName} must be a valid UUID");
        }
    }

    /**
     * Validate amount is positive numeric string.
     *
     * @param string $amount The amount to validate
     * @param string $fieldName Name of the field for error messages
     * @throws InvalidArgumentException If validation fails
     */
    protected function validatePositiveAmount(string $amount, string $fieldName = 'amount'): void
    {
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException("{$fieldName} must be a numeric value");
        }

        if (bccomp($amount, '0', 8) <= 0) {
            throw new InvalidArgumentException("{$fieldName} must be greater than zero");
        }

        // Check for reasonable precision (max 18 decimals)
        if (preg_match('/\.(\d+)$/', $amount, $matches) && strlen($matches[1]) > 18) {
            throw new InvalidArgumentException("{$fieldName} has too many decimal places (max 18)");
        }
    }

    /**
     * Validate amount is non-negative numeric string.
     *
     * @param string $amount The amount to validate
     * @param string $fieldName Name of the field for error messages
     * @throws InvalidArgumentException If validation fails
     */
    protected function validateNonNegativeAmount(string $amount, string $fieldName = 'amount'): void
    {
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException("{$fieldName} must be a numeric value");
        }

        if (bccomp($amount, '0', 8) < 0) {
            throw new InvalidArgumentException("{$fieldName} cannot be negative");
        }
    }

    /**
     * Validate integer amount is positive.
     *
     * @param int $amount The amount to validate
     * @param string $fieldName Name of the field for error messages
     * @throws InvalidArgumentException If validation fails
     */
    protected function validatePositiveIntegerAmount(int $amount, string $fieldName = 'amount'): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException("{$fieldName} must be greater than zero");
        }
    }

    /**
     * Validate asset code format.
     *
     * @param string $code The asset code to validate
     * @param string $fieldName Name of the field for error messages
     * @throws InvalidArgumentException If validation fails
     */
    protected function validateAssetCode(string $code, string $fieldName = 'asset code'): void
    {
        if (empty($code)) {
            throw new InvalidArgumentException("{$fieldName} cannot be empty");
        }

        // Asset codes: 2-10 uppercase alphanumeric characters
        if (! preg_match('/^[A-Z0-9]{2,10}$/', strtoupper($code))) {
            throw new InvalidArgumentException("{$fieldName} must be 2-10 alphanumeric characters");
        }
    }

    /**
     * Validate currency code (ISO 4217).
     *
     * @param string $code The currency code to validate
     * @param string $fieldName Name of the field for error messages
     * @throws InvalidArgumentException If validation fails
     */
    protected function validateCurrencyCode(string $code, string $fieldName = 'currency'): void
    {
        if (empty($code)) {
            throw new InvalidArgumentException("{$fieldName} cannot be empty");
        }

        // ISO 4217: 3 uppercase letters
        if (! preg_match('/^[A-Z]{3}$/', strtoupper($code))) {
            throw new InvalidArgumentException("{$fieldName} must be a valid ISO 4217 currency code");
        }
    }

    /**
     * Validate exchange rate.
     *
     * @param string $rate The exchange rate to validate
     * @param string $fieldName Name of the field for error messages
     * @throws InvalidArgumentException If validation fails
     */
    protected function validateExchangeRate(string $rate, string $fieldName = 'exchange rate'): void
    {
        if (! is_numeric($rate)) {
            throw new InvalidArgumentException("{$fieldName} must be a numeric value");
        }

        if (bccomp($rate, '0', 8) <= 0) {
            throw new InvalidArgumentException("{$fieldName} must be greater than zero");
        }

        // Exchange rates should be reasonable (0.0000001 to 10000000)
        if (bccomp($rate, '0.0000001', 8) < 0 || bccomp($rate, '10000000', 8) > 0) {
            throw new InvalidArgumentException("{$fieldName} is outside reasonable bounds");
        }
    }

    /**
     * Validate reference string (prevent injection).
     *
     * @param string $reference The reference to validate
     * @param string $fieldName Name of the field for error messages
     * @throws InvalidArgumentException If validation fails
     */
    protected function validateReference(string $reference, string $fieldName = 'reference'): void
    {
        // Max 255 characters
        if (strlen($reference) > 255) {
            throw new InvalidArgumentException("{$fieldName} cannot exceed 255 characters");
        }

        // Prevent common injection patterns
        if (preg_match('/<script|javascript:|on\w+=/i', $reference)) {
            throw new InvalidArgumentException("{$fieldName} contains invalid characters");
        }
    }

    /**
     * Validate metadata array.
     *
     * @param array<string, mixed> $metadata The metadata to validate
     * @param string $fieldName Name of the field for error messages
     * @throws InvalidArgumentException If validation fails
     */
    protected function validateMetadata(array $metadata, string $fieldName = 'metadata'): void
    {
        // Limit metadata size
        $jsonSize = strlen(json_encode($metadata) ?: '');
        if ($jsonSize > 65535) {
            throw new InvalidArgumentException("{$fieldName} exceeds maximum size (64KB)");
        }

        // Validate keys are strings
        foreach (array_keys($metadata) as $key) {
            if (! is_string($key)) {
                throw new InvalidArgumentException("{$fieldName} keys must be strings");
            }
        }
    }

    /**
     * Validate payment method.
     *
     * @param string $method The payment method to validate
     * @param array<int, string> $validMethods List of valid methods
     * @param string $fieldName Name of the field for error messages
     * @throws InvalidArgumentException If validation fails
     */
    protected function validatePaymentMethod(
        string $method,
        array $validMethods,
        string $fieldName = 'payment method'
    ): void {
        if (! in_array($method, $validMethods, true)) {
            throw new InvalidArgumentException(
                "{$fieldName} must be one of: " . implode(', ', $validMethods)
            );
        }
    }

    /**
     * Validate lock ID format.
     *
     * @param string $lockId The lock ID to validate
     * @param string $fieldName Name of the field for error messages
     * @throws InvalidArgumentException If validation fails
     */
    protected function validateLockId(string $lockId, string $fieldName = 'lock ID'): void
    {
        if (empty($lockId)) {
            throw new InvalidArgumentException("{$fieldName} cannot be empty");
        }

        // Lock IDs: lock_ prefix followed by UUID
        if (! preg_match('/^lock_[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $lockId)) {
            throw new InvalidArgumentException("{$fieldName} has invalid format");
        }
    }
}
