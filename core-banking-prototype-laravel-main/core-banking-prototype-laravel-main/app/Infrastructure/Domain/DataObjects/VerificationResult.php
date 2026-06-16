<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain\DataObjects;

/**
 * Result of a domain verification check.
 *
 * @immutable
 */
final readonly class VerificationResult
{
    /**
     * @param bool $valid Whether the domain passed all checks
     * @param string $domain Domain that was verified
     * @param array<string, bool> $checks Individual check results (name => passed)
     * @param array<string> $errors Error messages for failed checks
     * @param array<string> $warnings Non-fatal warnings
     */
    public function __construct(
        public bool $valid,
        public string $domain,
        public array $checks = [],
        public array $errors = [],
        public array $warnings = [],
    ) {
    }

    /**
     * Create from check results.
     *
     * @param array<string, bool> $checks
     * @param array<string> $errors
     * @param array<string> $warnings
     */
    public static function fromChecks(
        string $domain,
        array $checks,
        array $errors = [],
        array $warnings = [],
    ): self {
        $valid = ! in_array(false, $checks, true) && empty($errors);

        return new self(
            valid: $valid,
            domain: $domain,
            checks: $checks,
            errors: $errors,
            warnings: $warnings,
        );
    }

    /**
     * Get passed checks count.
     */
    public function getPassedCount(): int
    {
        return count(array_filter($this->checks));
    }

    /**
     * Get failed checks count.
     */
    public function getFailedCount(): int
    {
        return count($this->checks) - $this->getPassedCount();
    }

    /**
     * Get failed check names.
     *
     * @return array<string>
     */
    public function getFailedChecks(): array
    {
        return array_keys(array_filter($this->checks, fn ($passed) => ! $passed));
    }

    /**
     * Get a summary message.
     */
    public function getSummary(): string
    {
        $total = count($this->checks);
        $passed = $this->getPassedCount();

        if ($this->valid) {
            return "Domain {$this->domain} verified: {$passed}/{$total} checks passed";
        }

        $failed = $this->getFailedChecks();

        return "Domain {$this->domain} verification failed: " . implode(', ', $failed);
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'valid'    => $this->valid,
            'domain'   => $this->domain,
            'checks'   => $this->checks,
            'passed'   => $this->getPassedCount(),
            'failed'   => $this->getFailedCount(),
            'errors'   => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
