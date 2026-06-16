<?php

declare(strict_types=1);

namespace App\Domain\Webhook\Services;

/**
 * Sanitizes outbound webhook payloads by redacting sensitive fields.
 *
 * Any key whose name contains a sensitive pattern (case-insensitive) is replaced
 * with the string '[REDACTED]' before the payload is stored or dispatched.
 * This prevents credentials, PAN data, and other secrets from leaking to
 * third-party webhook consumers.
 */
final class PayloadSanitizer
{
    private const SENSITIVE_PATTERNS = [
        'secret',
        'key',
        'token',
        'password',
        'ssn',
        'pan',
        'cvv',
        'pin',
    ];

    /**
     * Sanitize an outbound webhook payload, redacting sensitive keys at every depth.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function sanitize(array $payload): array
    {
        return $this->recursiveSanitize($payload);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function recursiveSanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->recursiveSanitize($value);
            } elseif (is_string($key) && $this->isSensitiveKey($key)) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }

    private function isSensitiveKey(string $key): bool
    {
        $lower = strtolower($key);

        foreach (self::SENSITIVE_PATTERNS as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
