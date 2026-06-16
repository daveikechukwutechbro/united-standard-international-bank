<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain\DataObjects;

/**
 * Result of a domain installation operation.
 *
 * @immutable
 */
final readonly class InstallResult
{
    /**
     * @param bool $success Whether installation succeeded
     * @param string $domain Domain that was installed
     * @param array<string> $installedDependencies Dependencies that were also installed
     * @param array<string> $migrationsRun Migrations that were executed
     * @param array<string> $configsPublished Configuration files published
     * @param array<string> $errors Error messages if installation failed
     * @param array<string> $warnings Non-fatal warnings
     */
    public function __construct(
        public bool $success,
        public string $domain,
        public array $installedDependencies = [],
        public array $migrationsRun = [],
        public array $configsPublished = [],
        public array $errors = [],
        public array $warnings = [],
    ) {
    }

    /**
     * Create a successful result.
     *
     * @param array<string> $installedDependencies
     * @param array<string> $migrationsRun
     * @param array<string> $configsPublished
     * @param array<string> $warnings
     */
    public static function success(
        string $domain,
        array $installedDependencies = [],
        array $migrationsRun = [],
        array $configsPublished = [],
        array $warnings = [],
    ): self {
        return new self(
            success: true,
            domain: $domain,
            installedDependencies: $installedDependencies,
            migrationsRun: $migrationsRun,
            configsPublished: $configsPublished,
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
            return "Failed to install {$this->domain}: " . implode(', ', $this->errors);
        }

        $parts = ["Successfully installed {$this->domain}"];

        if (! empty($this->installedDependencies)) {
            $parts[] = 'Dependencies: ' . implode(', ', $this->installedDependencies);
        }

        if (! empty($this->migrationsRun)) {
            $parts[] = count($this->migrationsRun) . ' migrations executed';
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
            'success'                => $this->success,
            'domain'                 => $this->domain,
            'installed_dependencies' => $this->installedDependencies,
            'migrations_run'         => $this->migrationsRun,
            'configs_published'      => $this->configsPublished,
            'errors'                 => $this->errors,
            'warnings'               => $this->warnings,
        ];
    }
}
