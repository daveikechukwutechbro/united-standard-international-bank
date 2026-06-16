<?php

declare(strict_types=1);

namespace App\Domain\Shared\Logging;

use Illuminate\Support\Facades\Log;

/**
 * Audit logging for financial operations.
 *
 * Provides consistent audit trail across all shared interfaces
 * for compliance and security monitoring.
 */
trait AuditLogger
{
    /**
     * Log a financial operation audit event.
     *
     * @param string $operation The operation type
     * @param array<string, mixed> $context Context data for the audit
     * @param string $level Log level (info, warning, error)
     */
    protected function auditLog(string $operation, array $context = [], string $level = 'info'): void
    {
        $requestId = uniqid('audit_', true);
        if (app()->runningInConsole() === false) {
            $requestId = request()->header('X-Request-ID') ?? $requestId;
        }

        $auditData = [
            'audit_type' => 'financial_operation',
            'operation'  => $operation,
            'service'    => static::class,
            'timestamp'  => now()->toIso8601String(),
            'request_id' => $requestId,
            ...$context,
        ];

        // Sanitize sensitive data
        $auditData = $this->sanitizeAuditData($auditData);

        match ($level) {
            'warning' => Log::channel('audit')->warning($operation, $auditData),
            'error'   => Log::channel('audit')->error($operation, $auditData),
            default   => Log::channel('audit')->info($operation, $auditData),
        };
    }

    /**
     * Log operation start for tracking.
     *
     * @param string $operation The operation name
     * @param array<string, mixed> $params Operation parameters
     */
    protected function auditOperationStart(string $operation, array $params = []): void
    {
        $this->auditLog("{$operation}_started", [
            'status'     => 'started',
            'parameters' => $this->sanitizeAuditData($params),
        ]);
    }

    /**
     * Log operation success.
     *
     * @param string $operation The operation name
     * @param array<string, mixed> $result Operation result
     */
    protected function auditOperationSuccess(string $operation, array $result = []): void
    {
        $this->auditLog("{$operation}_completed", [
            'status' => 'success',
            'result' => $this->sanitizeAuditData($result),
        ]);
    }

    /**
     * Log operation failure.
     *
     * @param string $operation The operation name
     * @param string $reason Failure reason
     * @param array<string, mixed> $context Additional context
     */
    protected function auditOperationFailure(string $operation, string $reason, array $context = []): void
    {
        $this->auditLog("{$operation}_failed", [
            'status' => 'failed',
            'reason' => $reason,
            ...$context,
        ], 'error');
    }

    /**
     * Log security-related event.
     *
     * @param string $event Security event type
     * @param array<string, mixed> $context Event context
     */
    protected function auditSecurityEvent(string $event, array $context = []): void
    {
        $ipAddress = 'unknown';
        $userAgent = 'unknown';

        if (app()->runningInConsole() === false) {
            $ipAddress = request()->ip() ?? 'unknown';
            $userAgent = request()->userAgent() ?? 'unknown';
        }

        $this->auditLog("security_{$event}", [
            'security_event' => true,
            'ip_address'     => $ipAddress,
            'user_agent'     => $userAgent,
            ...$context,
        ], 'warning');
    }

    /**
     * Sanitize audit data to remove sensitive information.
     *
     * @param array<string, mixed> $data Data to sanitize
     * @return array<string, mixed> Sanitized data
     */
    private function sanitizeAuditData(array $data): array
    {
        $sensitiveKeys = [
            'password',
            'secret',
            'token',
            'api_key',
            'private_key',
            'credential',
            'authorization',
            'card_number',
            'cvv',
            'pin',
        ];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);

            // Check if key contains sensitive patterns
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (str_contains($lowerKey, $sensitiveKey)) {
                    $data[$key] = '[REDACTED]';

                    continue 2;
                }
            }

            // Recursively sanitize nested arrays
            if (is_array($value)) {
                $data[$key] = $this->sanitizeAuditData($value);
            }
        }

        return $data;
    }
}
