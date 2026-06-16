<?php

declare(strict_types=1);

namespace Tests\Domain\Governance\Strategies;

use App\Domain\Governance\Models\Poll;
use App\Domain\Governance\Strategies\OneUserOneVoteStrategy;
use App\Models\User;
use Mockery;
use Mockery\MockInterface;
use Tests\UnitTestCase;

class OneUserOneVoteStrategyTest extends UnitTestCase
{
    private OneUserOneVoteStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->strategy = new OneUserOneVoteStrategy();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ===========================================
    // getName Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_strategy_name(): void
    {
        expect($this->strategy->getName())->toBe('one_user_one_vote');
    }

    // ===========================================
    // getDescription Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_description(): void
    {
        expect($this->strategy->getDescription())
            ->toBe('Each user gets exactly one vote regardless of assets or account balance');
    }

    // ===========================================
    // calculatePower Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_always_returns_one_voting_power(): void
    {
        $user = $this->createMockUser(exists: true);
        $poll = $this->createMockPoll();

        $power = $this->strategy->calculatePower($user, $poll);

        expect($power)->toBe(1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_one_power_even_for_different_users(): void
    {
        $poll = $this->createMockPoll();

        // Multiple different users should all get power of 1
        for ($i = 1; $i <= 5; $i++) {
            $user = $this->createMockUser(exists: true);
            expect($this->strategy->calculatePower($user, $poll))->toBe(1);
        }
    }

    // ===========================================
    // canVote Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_existing_user_to_vote(): void
    {
        $user = $this->createMockUser(exists: true);
        $poll = $this->createMockPoll();

        expect($this->strategy->canVote($user, $poll))->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_disallows_non_existing_user_to_vote(): void
    {
        $user = $this->createMockUser(exists: false);
        $poll = $this->createMockPoll();

        expect($this->strategy->canVote($user, $poll))->toBeFalse();
    }

    // ===========================================
    // getMaxVotingPower Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_one_as_max_voting_power(): void
    {
        $poll = $this->createMockPoll();

        expect($this->strategy->getMaxVotingPower($poll))->toBe(1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_same_max_power_for_any_poll(): void
    {
        // Max power should be 1 regardless of poll configuration
        for ($i = 1; $i <= 3; $i++) {
            $poll = $this->createMockPoll();
            expect($this->strategy->getMaxVotingPower($poll))->toBe(1);
        }
    }

    // ===========================================
    // Interface Compliance Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_implements_voting_power_strategy_interface(): void
    {
        expect($this->strategy)->toBeInstanceOf(\App\Domain\Governance\Contracts\IVotingPowerStrategy::class);
    }

    // ===========================================
    // Helper Methods
    // ===========================================

    /**
     * @return User&MockInterface
     */
    private function createMockUser(bool $exists): User
    {
        /** @var User&MockInterface $user */
        $user = Mockery::mock(User::class);
        $user->shouldReceive('exists')->andReturn($exists);

        return $user;
    }

    /**
     * @return Poll&MockInterface
     */
    private function createMockPoll(): Poll
    {
        /** @var Poll&MockInterface $poll */
        $poll = Mockery::mock(Poll::class);

        return $poll;
    }
}
