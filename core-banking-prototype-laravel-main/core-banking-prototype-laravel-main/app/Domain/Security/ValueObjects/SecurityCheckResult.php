<?php

declare(strict_types=1);

namespace App\Domain\Security\ValueObjects;

class SecurityCheckResult
{
    /**
     * @param  string  $name  Check name
     * @param  string  $category  OWASP category
     * @param  bool  $passed  Whether the check passed
     * @param  int  $score  Score 0-100
     * @param  array<string>  $findings  Issues found
     * @param  array<string>  $recommendations  Suggested fixes
     * @param  string  $severity  'critical', 'high', 'medium', 'low', 'info'
     */
    public function __construct(
        public readonly string $name,
        public readonly string $category,
        public readonly bool $passed,
        public readonly int $score,
        public readonly array $findings = [],
        public readonly array $recommendations = [],
        public readonly string $severity = 'info',
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name'            => $this->name,
            'category'        => $this->category,
            'passed'          => $this->passed,
            'score'           => $this->score,
            'findings'        => $this->findings,
            'recommendations' => $this->recommendations,
            'severity'        => $this->severity,
        ];
    }
}
