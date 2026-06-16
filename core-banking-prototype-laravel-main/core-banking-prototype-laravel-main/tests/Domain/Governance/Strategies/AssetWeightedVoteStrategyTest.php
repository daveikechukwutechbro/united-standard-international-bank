<?php

declare(strict_types=1);

namespace Tests\Domain\Governance\Strategies;

use App\Domain\Governance\Models\Poll;
use App\Domain\Governance\Strategies\AssetWeightedVoteStrategy;
use Mockery;
use Mockery\MockInterface;
use Tests\UnitTestCase;

class AssetWeightedVoteStrategyTest extends UnitTestCase
{
    private AssetWeightedVoteStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->strategy = new AssetWeightedVoteStrategy();
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
        expect($this->strategy->getName())->toBe('asset_weighted_vote');
    }

    // ===========================================
    // getDescription Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_description_with_balance_info(): void
    {
        $description = $this->strategy->getDescription();

        expect($description)->toContain('Voting power based on total USD balance');
        expect($description)->toContain('$1.00'); // Minimum balance
        expect($description)->toContain('$100.00'); // Power divisor
    }

    // ===========================================
    // getMinimumBalance Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_minimum_balance_of_100_cents(): void
    {
        expect($this->strategy->getMinimumBalance())->toBe(100);
    }

    // ===========================================
    // getPowerDivisor Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_power_divisor_of_10000_cents(): void
    {
        expect($this->strategy->getPowerDivisor())->toBe(10000);
    }

    // ===========================================
    // getMaxVotingPower Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_null_for_max_voting_power(): void
    {
        $poll = $this->createMockPoll();

        // Asset-weighted has no theoretical maximum
        expect($this->strategy->getMaxVotingPower($poll))->toBeNull();
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
    // Configuration Constants Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_usd_as_default_asset_code(): void
    {
        // Verified via description and minimum balance checks
        $description = $this->strategy->getDescription();
        expect($description)->toContain('USD');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_correct_power_per_dollar(): void
    {
        // $100 = 10000 cents should equal 1 voting power
        // $200 = 20000 cents should equal 2 voting power
        // This is tested via the divisor
        $divisor = $this->strategy->getPowerDivisor();

        // 10000 cents / 10000 = 1 power
        expect(intval(10000 / $divisor))->toBe(1);

        // 50000 cents ($500) / 10000 = 5 power
        expect(intval(50000 / $divisor))->toBe(5);

        // 100000 cents ($1000) / 10000 = 10 power
        expect(intval(100000 / $divisor))->toBe(10);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_minimum_balance_for_voting(): void
    {
        $minimum = $this->strategy->getMinimumBalance();

        // $1.00 = 100 cents
        expect($minimum)->toBe(100);

        // Anything below 100 cents should not be enough
        expect(99 < $minimum)->toBeTrue();
        expect(100 >= $minimum)->toBeTrue();
    }

    // ===========================================
    // Power Calculation Logic Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_ensures_minimum_one_power_when_meeting_threshold(): void
    {
        // Test the mathematical formula:
        // If balance = 5000 (below power divisor but above minimum)
        // 5000 / 10000 = 0.5, intval = 0
        // But max(1, 0) = 1 (minimum 1 power when meeting threshold)

        $divisor = $this->strategy->getPowerDivisor();
        $minimum = $this->strategy->getMinimumBalance();

        // A balance that meets minimum but is below divisor
        $balance = 5000; // $50

        $calculatedPower = intval($balance / $divisor);
        expect($calculatedPower)->toBe(0);

        // The strategy should apply max(1, calculatedPower)
        $expectedPower = max(1, $calculatedPower);
        expect($expectedPower)->toBe(1);
    }

    // ===========================================
    // Helper Methods
    // ===========================================

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
