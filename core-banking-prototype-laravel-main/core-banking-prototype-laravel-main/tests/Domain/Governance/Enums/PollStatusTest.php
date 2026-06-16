<?php

declare(strict_types=1);

namespace Tests\Domain\Governance\Enums;

use App\Domain\Governance\Enums\PollStatus;
use Tests\UnitTestCase;

class PollStatusTest extends UnitTestCase
{
    // ===========================================
    // Status Values Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_all_expected_status_values(): void
    {
        $statuses = PollStatus::cases();

        expect($statuses)->toHaveCount(7);
        expect(array_column($statuses, 'value'))->toBe([
            'draft',
            'pending',
            'active',
            'closed',
            'executed',
            'cancelled',
            'failed',
        ]);
    }

    // ===========================================
    // getLabel Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_labels(): void
    {
        expect(PollStatus::DRAFT->getLabel())->toBe('Draft');
        expect(PollStatus::PENDING->getLabel())->toBe('Pending');
        expect(PollStatus::ACTIVE->getLabel())->toBe('Active');
        expect(PollStatus::CLOSED->getLabel())->toBe('Closed');
        expect(PollStatus::EXECUTED->getLabel())->toBe('Executed');
        expect(PollStatus::CANCELLED->getLabel())->toBe('Cancelled');
        expect(PollStatus::FAILED->getLabel())->toBe('Failed');
    }

    // ===========================================
    // getColor Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_colors(): void
    {
        expect(PollStatus::DRAFT->getColor())->toBe('gray');
        expect(PollStatus::PENDING->getColor())->toBe('yellow');
        expect(PollStatus::ACTIVE->getColor())->toBe('green');
        expect(PollStatus::CLOSED->getColor())->toBe('blue');
        expect(PollStatus::EXECUTED->getColor())->toBe('purple');
        expect(PollStatus::CANCELLED->getColor())->toBe('red');
        expect(PollStatus::FAILED->getColor())->toBe('red');
    }

    // ===========================================
    // canVote Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_only_allows_voting_on_active_polls(): void
    {
        expect(PollStatus::ACTIVE->canVote())->toBeTrue();

        expect(PollStatus::DRAFT->canVote())->toBeFalse();
        expect(PollStatus::PENDING->canVote())->toBeFalse();
        expect(PollStatus::CLOSED->canVote())->toBeFalse();
        expect(PollStatus::EXECUTED->canVote())->toBeFalse();
        expect(PollStatus::CANCELLED->canVote())->toBeFalse();
        expect(PollStatus::FAILED->canVote())->toBeFalse();
    }

    // ===========================================
    // canModify Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_only_allows_modification_on_draft_and_pending(): void
    {
        expect(PollStatus::DRAFT->canModify())->toBeTrue();
        expect(PollStatus::PENDING->canModify())->toBeTrue();

        expect(PollStatus::ACTIVE->canModify())->toBeFalse();
        expect(PollStatus::CLOSED->canModify())->toBeFalse();
        expect(PollStatus::EXECUTED->canModify())->toBeFalse();
        expect(PollStatus::CANCELLED->canModify())->toBeFalse();
        expect(PollStatus::FAILED->canModify())->toBeFalse();
    }

    // ===========================================
    // isFinalized Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_marks_executed_cancelled_failed_as_finalized(): void
    {
        expect(PollStatus::EXECUTED->isFinalized())->toBeTrue();
        expect(PollStatus::CANCELLED->isFinalized())->toBeTrue();
        expect(PollStatus::FAILED->isFinalized())->toBeTrue();

        expect(PollStatus::DRAFT->isFinalized())->toBeFalse();
        expect(PollStatus::PENDING->isFinalized())->toBeFalse();
        expect(PollStatus::ACTIVE->isFinalized())->toBeFalse();
        expect(PollStatus::CLOSED->isFinalized())->toBeFalse();
    }

    // ===========================================
    // Value Casting Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_from_string_value(): void
    {
        expect(PollStatus::from('draft'))->toBe(PollStatus::DRAFT);
        expect(PollStatus::from('active'))->toBe(PollStatus::ACTIVE);
        expect(PollStatus::from('executed'))->toBe(PollStatus::EXECUTED);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_null_for_invalid_value(): void
    {
        expect(PollStatus::tryFrom('invalid'))->toBeNull();
        expect(PollStatus::tryFrom(''))->toBeNull();
    }
}
