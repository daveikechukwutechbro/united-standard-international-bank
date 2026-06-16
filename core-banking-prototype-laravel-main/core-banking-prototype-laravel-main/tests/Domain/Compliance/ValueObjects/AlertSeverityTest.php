<?php

declare(strict_types=1);

namespace Tests\Domain\Compliance\ValueObjects;

use App\Domain\Compliance\ValueObjects\AlertSeverity;
use InvalidArgumentException;
use Tests\UnitTestCase;

class AlertSeverityTest extends UnitTestCase
{
    // ===========================================
    // Constructor Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_with_valid_severity(): void
    {
        $severity = new AlertSeverity('high');

        expect($severity->value())->toBe('high');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_accepts_all_valid_severities(): void
    {
        $validSeverities = ['low', 'medium', 'high', 'critical'];

        foreach ($validSeverities as $severityValue) {
            $severity = new AlertSeverity($severityValue);
            expect($severity->value())->toBe($severityValue);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_normalizes_to_lowercase(): void
    {
        $severity1 = new AlertSeverity('HIGH');
        $severity2 = new AlertSeverity('Critical');
        $severity3 = new AlertSeverity('MEDIUM');

        expect($severity1->value())->toBe('high');
        expect($severity2->value())->toBe('critical');
        expect($severity3->value())->toBe('medium');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_invalid_severity(): void
    {
        expect(fn () => new AlertSeverity('invalid'))
            ->toThrow(InvalidArgumentException::class, 'Invalid alert severity: invalid');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_empty_severity(): void
    {
        expect(fn () => new AlertSeverity(''))
            ->toThrow(InvalidArgumentException::class);
    }

    // ===========================================
    // priority Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_priority(): void
    {
        expect((new AlertSeverity('low'))->priority())->toBe(1);
        expect((new AlertSeverity('medium'))->priority())->toBe(2);
        expect((new AlertSeverity('high'))->priority())->toBe(3);
        expect((new AlertSeverity('critical'))->priority())->toBe(4);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_priorities_increase_with_severity(): void
    {
        $low = new AlertSeverity('low');
        $medium = new AlertSeverity('medium');
        $high = new AlertSeverity('high');
        $critical = new AlertSeverity('critical');

        expect($low->priority())->toBeLessThan($medium->priority());
        expect($medium->priority())->toBeLessThan($high->priority());
        expect($high->priority())->toBeLessThan($critical->priority());
    }

    // ===========================================
    // isHigherThan Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_compares_higher_severity(): void
    {
        $low = new AlertSeverity('low');
        $medium = new AlertSeverity('medium');
        $high = new AlertSeverity('high');
        $critical = new AlertSeverity('critical');

        expect($critical->isHigherThan($high))->toBeTrue();
        expect($high->isHigherThan($medium))->toBeTrue();
        expect($medium->isHigherThan($low))->toBeTrue();
        expect($low->isHigherThan($critical))->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_false_when_comparing_equal_severity(): void
    {
        $high1 = new AlertSeverity('high');
        $high2 = new AlertSeverity('high');

        expect($high1->isHigherThan($high2))->toBeFalse();
    }

    // ===========================================
    // isLowerThan Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_compares_lower_severity(): void
    {
        $low = new AlertSeverity('low');
        $medium = new AlertSeverity('medium');
        $high = new AlertSeverity('high');
        $critical = new AlertSeverity('critical');

        expect($low->isLowerThan($medium))->toBeTrue();
        expect($medium->isLowerThan($high))->toBeTrue();
        expect($high->isLowerThan($critical))->toBeTrue();
        expect($critical->isLowerThan($low))->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_false_when_comparing_equal_severity_for_lower(): void
    {
        $medium1 = new AlertSeverity('medium');
        $medium2 = new AlertSeverity('medium');

        expect($medium1->isLowerThan($medium2))->toBeFalse();
    }

    // ===========================================
    // isCritical Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_critical_severity(): void
    {
        $critical = new AlertSeverity('critical');
        $high = new AlertSeverity('high');

        expect($critical->isCritical())->toBeTrue();
        expect($high->isCritical())->toBeFalse();
    }

    // ===========================================
    // isHigh Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_high_severity(): void
    {
        $high = new AlertSeverity('high');
        $medium = new AlertSeverity('medium');
        $critical = new AlertSeverity('critical');

        expect($high->isHigh())->toBeTrue();
        expect($medium->isHigh())->toBeFalse();
        expect($critical->isHigh())->toBeFalse();
    }

    // ===========================================
    // requiresImmediateAction Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_immediate_action_for_critical_and_high(): void
    {
        $critical = new AlertSeverity('critical');
        $high = new AlertSeverity('high');
        $medium = new AlertSeverity('medium');
        $low = new AlertSeverity('low');

        expect($critical->requiresImmediateAction())->toBeTrue();
        expect($high->requiresImmediateAction())->toBeTrue();
        expect($medium->requiresImmediateAction())->toBeFalse();
        expect($low->requiresImmediateAction())->toBeFalse();
    }

    // ===========================================
    // __toString Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_converts_to_string(): void
    {
        $severity = new AlertSeverity('critical');

        expect((string) $severity)->toBe('critical');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_used_in_string_context(): void
    {
        $severity = new AlertSeverity('high');

        expect("Severity: {$severity}")->toBe('Severity: high');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_preserves_lowercase_in_string_output(): void
    {
        $severity = new AlertSeverity('CRITICAL');

        expect((string) $severity)->toBe('critical');
    }
}
