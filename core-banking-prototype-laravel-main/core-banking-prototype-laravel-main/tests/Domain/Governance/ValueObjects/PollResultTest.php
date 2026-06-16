<?php

declare(strict_types=1);

namespace Tests\Domain\Governance\ValueObjects;

use App\Domain\Governance\ValueObjects\PollResult;
use Tests\UnitTestCase;

class PollResultTest extends UnitTestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_result_with_constructor(): void
    {
        $result = new PollResult(
            pollUuid: 'poll-123',
            totalVotes: 100,
            totalVotingPower: 500,
            optionResults: [
                'yes' => ['votes' => 60, 'power' => 300, 'percentage' => 60.0],
                'no'  => ['votes' => 40, 'power' => 200, 'percentage' => 40.0],
            ],
            participationRate: 75.0,
            winningOption: 'yes',
            metadata: ['source' => 'test']
        );

        expect($result->pollUuid)->toBe('poll-123');
        expect($result->totalVotes)->toBe(100);
        expect($result->totalVotingPower)->toBe(500);
        expect($result->participationRate)->toBe(75.0);
        expect($result->winningOption)->toBe('yes');
        expect($result->metadata)->toBe(['source' => 'test']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_result_from_votes(): void
    {
        $votes = [
            ['selected_options' => ['yes'], 'voting_power' => 100],
            ['selected_options' => ['yes'], 'voting_power' => 50],
            ['selected_options' => ['no'], 'voting_power' => 75],
        ];

        $options = [
            ['id' => 'yes'],
            ['id' => 'no'],
        ];

        $result = PollResult::calculate(
            pollUuid: 'poll-456',
            votes: $votes,
            options: $options,
            totalEligibleVotingPower: 300
        );

        expect($result->pollUuid)->toBe('poll-456');
        expect($result->totalVotes)->toBe(3);
        expect($result->totalVotingPower)->toBe(225); // 100 + 50 + 75
        expect($result->winningOption)->toBe('yes');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_percentages_correctly(): void
    {
        $votes = [
            ['selected_options' => ['a'], 'voting_power' => 50],
            ['selected_options' => ['b'], 'voting_power' => 30],
            ['selected_options' => ['c'], 'voting_power' => 20],
        ];

        $options = [
            ['id' => 'a'],
            ['id' => 'b'],
            ['id' => 'c'],
        ];

        $result = PollResult::calculate(
            pollUuid: 'poll-789',
            votes: $votes,
            options: $options,
            totalEligibleVotingPower: 100
        );

        expect($result->optionResults['a']['percentage'])->toBe(50.0);
        expect($result->optionResults['b']['percentage'])->toBe(30.0);
        expect($result->optionResults['c']['percentage'])->toBe(20.0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_participation_rate(): void
    {
        $votes = [
            ['selected_options' => ['yes'], 'voting_power' => 100],
        ];

        $options = [['id' => 'yes'], ['id' => 'no']];

        $result = PollResult::calculate(
            pollUuid: 'poll-rate',
            votes: $votes,
            options: $options,
            totalEligibleVotingPower: 400
        );

        expect($result->participationRate)->toBe(25.0); // 100/400 * 100
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_empty_votes(): void
    {
        $options = [['id' => 'yes'], ['id' => 'no']];

        $result = PollResult::calculate(
            pollUuid: 'poll-empty',
            votes: [],
            options: $options,
            totalEligibleVotingPower: 100
        );

        expect($result->totalVotes)->toBe(0);
        expect($result->totalVotingPower)->toBe(0);
        expect($result->participationRate)->toBe(0.0);
        expect($result->winningOption)->toBeNull();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_zero_eligible_voting_power(): void
    {
        $votes = [
            ['selected_options' => ['yes'], 'voting_power' => 10],
        ];

        $options = [['id' => 'yes'], ['id' => 'no']];

        $result = PollResult::calculate(
            pollUuid: 'poll-zero',
            votes: $votes,
            options: $options,
            totalEligibleVotingPower: 0
        );

        expect($result->participationRate)->toBe(0.0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_converts_to_array(): void
    {
        $result = new PollResult(
            pollUuid: 'poll-array',
            totalVotes: 50,
            totalVotingPower: 200,
            optionResults: ['a' => ['votes' => 50, 'power' => 200, 'percentage' => 100.0]],
            participationRate: 80.0,
            winningOption: 'a',
            metadata: ['key' => 'value']
        );

        $array = $result->toArray();

        expect($array['poll_uuid'])->toBe('poll-array');
        expect($array['total_votes'])->toBe(50);
        expect($array['total_voting_power'])->toBe(200);
        expect($array['participation_rate'])->toBe(80.0);
        expect($array['winning_option'])->toBe('a');
        expect($array['metadata'])->toBe(['key' => 'value']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_gets_option_result(): void
    {
        $result = new PollResult(
            pollUuid: 'poll-get',
            totalVotes: 10,
            totalVotingPower: 100,
            optionResults: [
                'a' => ['votes' => 5, 'power' => 60, 'percentage' => 60.0],
                'b' => ['votes' => 5, 'power' => 40, 'percentage' => 40.0],
            ],
            participationRate: 50.0
        );

        $optionA = $result->getOptionResult('a');
        expect($optionA)->not->toBeNull();
        assert($optionA !== null);
        expect($optionA['votes'])->toBe(5);
        expect($optionA['power'])->toBe(60);
        expect($optionA['percentage'])->toBe(60.0);

        $optionC = $result->getOptionResult('c');
        expect($optionC)->toBeNull();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_checks_for_winner(): void
    {
        $withWinner = new PollResult(
            pollUuid: 'poll-winner',
            totalVotes: 10,
            totalVotingPower: 100,
            optionResults: [],
            participationRate: 50.0,
            winningOption: 'a'
        );

        $withoutWinner = new PollResult(
            pollUuid: 'poll-no-winner',
            totalVotes: 0,
            totalVotingPower: 0,
            optionResults: [],
            participationRate: 0.0,
            winningOption: null
        );

        expect($withWinner->hasWinner())->toBeTrue();
        expect($withoutWinner->hasWinner())->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_checks_participation_threshold(): void
    {
        $result = new PollResult(
            pollUuid: 'poll-threshold',
            totalVotes: 100,
            totalVotingPower: 500,
            optionResults: [],
            participationRate: 50.0
        );

        expect($result->meetsParticipationThreshold(50.0))->toBeTrue();
        expect($result->meetsParticipationThreshold(49.9))->toBeTrue();
        expect($result->meetsParticipationThreshold(50.1))->toBeFalse();
        expect($result->meetsParticipationThreshold(0.0))->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_multiple_choice_votes(): void
    {
        $votes = [
            ['selected_options' => ['a', 'b'], 'voting_power' => 100],
            ['selected_options' => ['a'], 'voting_power' => 50],
        ];

        $options = [
            ['id' => 'a'],
            ['id' => 'b'],
            ['id' => 'c'],
        ];

        $result = PollResult::calculate(
            pollUuid: 'poll-multi',
            votes: $votes,
            options: $options,
            totalEligibleVotingPower: 200
        );

        // Option 'a' gets 2 votes (150 power), 'b' gets 1 vote (100 power)
        expect($result->optionResults['a']['votes'])->toBe(2);
        expect($result->optionResults['a']['power'])->toBe(150);
        expect($result->optionResults['b']['votes'])->toBe(1);
        expect($result->optionResults['b']['power'])->toBe(100);
        expect($result->optionResults['c']['votes'])->toBe(0);
        expect($result->optionResults['c']['power'])->toBe(0);
    }
}
