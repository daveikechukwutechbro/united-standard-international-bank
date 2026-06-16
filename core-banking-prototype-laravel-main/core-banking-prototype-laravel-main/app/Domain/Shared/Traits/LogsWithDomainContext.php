<?php

declare(strict_types=1);

namespace App\Domain\Shared\Traits;

use Illuminate\Support\Facades\Log;

trait LogsWithDomainContext
{
    /**
     * Get the domain name from the class namespace.
     */
    protected function getDomainName(): string
    {
        $namespace = static::class;

        if (preg_match('/App\\\\Domain\\\\([^\\\\]+)\\\\/', $namespace, $matches)) {
            return $matches[1];
        }

        return 'Unknown';
    }

    /**
     * Log a message with domain context.
     *
     * @param  array<string, mixed> $context
     */
    protected function logWithDomain(string $level, string $message, array $context = []): void
    {
        $context['domain'] = $this->getDomainName();
        $context['service'] = class_basename(static::class);

        Log::log($level, $message, $context);
    }

    /**
     * Log a debug message with domain context.
     *
     * @param  array<string, mixed> $context
     */
    protected function logDebug(string $message, array $context = []): void
    {
        $this->logWithDomain('debug', $message, $context);
    }

    /**
     * Log an info message with domain context.
     *
     * @param  array<string, mixed> $context
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->logWithDomain('info', $message, $context);
    }

    /**
     * Log a warning message with domain context.
     *
     * @param  array<string, mixed> $context
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->logWithDomain('warning', $message, $context);
    }

    /**
     * Log an error message with domain context.
     *
     * @param  array<string, mixed> $context
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->logWithDomain('error', $message, $context);
    }
}
