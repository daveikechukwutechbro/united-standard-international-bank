<?php

declare(strict_types=1);

namespace App\Domain\Security\ValueObjects;

use DateTimeImmutable;

class SecurityAuditReport
{
    /**
     * @param  array<SecurityCheckResult>  $checks
     */
    public function __construct(
        public readonly array $checks,
        public readonly int $overallScore,
        public readonly string $grade,
        public readonly DateTimeImmutable $timestamp,
    ) {
    }

    public function isPassing(int $minScore = 70): bool
    {
        return $this->overallScore >= $minScore;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'checks'        => array_map(fn (SecurityCheckResult $c) => $c->toArray(), $this->checks),
            'overall_score' => $this->overallScore,
            'grade'         => $this->grade,
            'timestamp'     => $this->timestamp->format('c'),
            'passing'       => $this->isPassing(),
        ];
    }

    public function toJson(): string
    {
        return (string) json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * Calculate grade from score.
     */
    public static function gradeFromScore(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default      => 'F',
        };
    }
}
