<?php

declare(strict_types=1);

namespace Tests\Domain\Compliance\ValueObjects;

use App\Domain\Compliance\ValueObjects\AlertStatus;
use InvalidArgumentException;
use Tests\UnitTestCase;

class AlertStatusTest extends UnitTestCase
{
    // ===========================================
    // Constructor Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_with_valid_status(): void
    {
        $status = new AlertStatus('open');

        expect($status->value())->toBe('open');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_accepts_all_valid_statuses(): void
    {
        $validStatuses = [
            'open',
            'in_review',
            'investigating',
            'escalated',
            'resolved',
            'closed',
            'false_positive',
        ];

        foreach ($validStatuses as $statusValue) {
            $status = new AlertStatus($statusValue);
            expect($status->value())->toBe($statusValue);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_invalid_status(): void
    {
        expect(fn () => new AlertStatus('invalid'))
            ->toThrow(InvalidArgumentException::class, 'Invalid alert status: invalid');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_empty_status(): void
    {
        expect(fn () => new AlertStatus(''))
            ->toThrow(InvalidArgumentException::class);
    }

    // ===========================================
    // isOpen Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_open_status(): void
    {
        $open = new AlertStatus('open');
        $investigating = new AlertStatus('investigating');

        expect($open->isOpen())->toBeTrue();
        expect($investigating->isOpen())->toBeFalse();
    }

    // ===========================================
    // isInvestigating Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_investigating_status(): void
    {
        $investigating = new AlertStatus('investigating');
        $open = new AlertStatus('open');

        expect($investigating->isInvestigating())->toBeTrue();
        expect($open->isInvestigating())->toBeFalse();
    }

    // ===========================================
    // isEscalated Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_escalated_status(): void
    {
        $escalated = new AlertStatus('escalated');
        $open = new AlertStatus('open');

        expect($escalated->isEscalated())->toBeTrue();
        expect($open->isEscalated())->toBeFalse();
    }

    // ===========================================
    // isResolved Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_resolved_status(): void
    {
        $resolved = new AlertStatus('resolved');
        $open = new AlertStatus('open');

        expect($resolved->isResolved())->toBeTrue();
        expect($open->isResolved())->toBeFalse();
    }

    // ===========================================
    // isClosed Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_closed_status(): void
    {
        $closed = new AlertStatus('closed');
        $resolved = new AlertStatus('resolved');
        $falsePositive = new AlertStatus('false_positive');
        $open = new AlertStatus('open');

        expect($closed->isClosed())->toBeTrue();
        expect($resolved->isClosed())->toBeTrue();
        expect($falsePositive->isClosed())->toBeTrue();
        expect($open->isClosed())->toBeFalse();
    }

    // ===========================================
    // canTransitionTo Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_valid_transitions_from_open(): void
    {
        $open = new AlertStatus('open');

        expect($open->canTransitionTo('investigating'))->toBeTrue();
        expect($open->canTransitionTo('escalated'))->toBeTrue();
        expect($open->canTransitionTo('resolved'))->toBeTrue();
        expect($open->canTransitionTo('false_positive'))->toBeTrue();
        expect($open->canTransitionTo('closed'))->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_valid_transitions_from_investigating(): void
    {
        $investigating = new AlertStatus('investigating');

        expect($investigating->canTransitionTo('escalated'))->toBeTrue();
        expect($investigating->canTransitionTo('resolved'))->toBeTrue();
        expect($investigating->canTransitionTo('false_positive'))->toBeTrue();
        expect($investigating->canTransitionTo('closed'))->toBeTrue();
        expect($investigating->canTransitionTo('open'))->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_valid_transitions_from_escalated(): void
    {
        $escalated = new AlertStatus('escalated');

        expect($escalated->canTransitionTo('resolved'))->toBeTrue();
        expect($escalated->canTransitionTo('closed'))->toBeTrue();
        expect($escalated->canTransitionTo('open'))->toBeFalse();
        expect($escalated->canTransitionTo('investigating'))->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_valid_transitions_from_resolved(): void
    {
        $resolved = new AlertStatus('resolved');

        expect($resolved->canTransitionTo('closed'))->toBeTrue();
        expect($resolved->canTransitionTo('open'))->toBeFalse();
        expect($resolved->canTransitionTo('escalated'))->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_all_transitions_from_closed(): void
    {
        $closed = new AlertStatus('closed');

        expect($closed->canTransitionTo('open'))->toBeFalse();
        expect($closed->canTransitionTo('investigating'))->toBeFalse();
        expect($closed->canTransitionTo('escalated'))->toBeFalse();
        expect($closed->canTransitionTo('resolved'))->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_false_positive_to_transition_to_closed_only(): void
    {
        $falsePositive = new AlertStatus('false_positive');

        expect($falsePositive->canTransitionTo('closed'))->toBeTrue();
        expect($falsePositive->canTransitionTo('open'))->toBeFalse();
        expect($falsePositive->canTransitionTo('investigating'))->toBeFalse();
    }

    // ===========================================
    // __toString Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_converts_to_string(): void
    {
        $status = new AlertStatus('investigating');

        expect((string) $status)->toBe('investigating');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_used_in_string_context(): void
    {
        $status = new AlertStatus('escalated');

        expect("Status: {$status}")->toBe('Status: escalated');
    }
}
