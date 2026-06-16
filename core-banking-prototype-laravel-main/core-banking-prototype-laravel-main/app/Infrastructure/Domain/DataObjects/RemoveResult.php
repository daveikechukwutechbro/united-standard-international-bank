<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain\DataObjects;

/**
 * Result of a domain removal operation.
 *
 * @immutable
 */
final readonly class RemoveResult
{
    /**
     * @param bool $success Whether removal succeeded
     * @param string $domain Domain that was removed
     * @param array<string> $migrationsReverted Migrations that were rolled back
     * @param array<string> $configsRemoved Configuration files removed
     * @param array<string> $errors Error messages if removal failed
     * @param array<string> $warnings Non-fatal warnings
     */
    public function __construct(
        public bool $success,
        public string $domain,
        public array $migrationsReverted = [],
        public array $configsRemoved = [],
        public array $errors = [],
        public array $warnings = [],
    ) {
    }

    /**
     * Create a successful result.
     *
     * @param array<string> $migrationsReverted
     * @param array<string> $configsRemoved
     * @param array<string> $warnings
     */
    public static function success(
        string $domain,
        array $migrationsReverted = [],
        array $configsRemoved = [],
        array $warnings = [],
    ): self {
        return new self(
            success: true,
            domain: $domain,
            migrationsReverted: $migrationsReverted,
            configsRemoved: $configsRemoved,
            warnings: $warnings,
        );
    }

    /**
     * Create a failed result.
     *
     * @param array<string> $errors
     */
    public static function failure(string $domain, array $errors): self
    {
        return new self(
            success: false,
            domain: $domain,
            errors: $errors,
        );
    }

    /**
     * Get a summary message.
     */
    public function getSummary(): string
    {
        if (! $this->success) {
            return "Failed to remove {$this->domain}: " . implode(', ', $this->errors);
        }

        $parts = ["Successfully removed {$this->domain}"];

        if (! empty($this->migrationsReverted)) {
            $parts[] = count($this->migrationsReverted) . ' migrations reverted';
        }

        return implode('. ', $parts);
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success'             => $this->success,
            'domain'              => $this->domain,
            'migrations_reverted' => $this->migrationsReverted,
            'configs_removed'     => $this->configsRemoved,
            'errors'              => $this->errors,
            'warnings'            => $this->warnings,
        ];
    }
}
