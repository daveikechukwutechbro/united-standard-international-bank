<?php

declare(strict_types=1);

namespace App\Domain\Governance\Strategies;

use App\Domain\Account\Models\Account;
use App\Domain\Governance\Contracts\IVotingPowerStrategy;
use App\Domain\Governance\Models\Poll;
use App\Models\User;

class AssetWeightedVoteStrategy implements IVotingPowerStrategy
{
    private const DEFAULT_ASSET_CODE = 'USD';

    private const MIN_BALANCE_FOR_VOTE = 100; // $1.00 in cents

    private const POWER_DIVISOR = 10000; // $100.00 = 1 voting power

    public function calculatePower(User $user, Poll $poll): int
    {
        $accounts = Account::where('user_uuid', $user->uuid)->get();

        if ($accounts->isEmpty()) {
            return 0;
        }

        $totalBalance = 0;

        // Sum up balances across all user accounts
        foreach ($accounts as $account) {
            $balance = $account->getBalance(self::DEFAULT_ASSET_CODE);
            $totalBalance += $balance;
        }

        // Minimum balance requirement
        if ($totalBalance < self::MIN_BALANCE_FOR_VOTE) {
            return 0;
        }

        // Convert balance to voting power
        // $100.00 (10000 cents) = 1 voting power
        $votingPower = intval($totalBalance / self::POWER_DIVISOR);

        // Ensure at least 1 voting power if minimum balance is met
        return max(1, $votingPower);
    }

    public function getName(): string
    {
        return 'asset_weighted_vote';
    }

    public function getDescription(): string
    {
        return sprintf(
            'Voting power based on total USD balance across accounts. Minimum $%.2f required. Every $%.2f equals 1 voting power.',
            self::MIN_BALANCE_FOR_VOTE / 100,
            self::POWER_DIVISOR / 100
        );
    }

    public function canVote(User $user, Poll $poll): bool
    {
        if (! $user->exists()) {
            return false;
        }

        return $this->calculatePower($user, $poll) > 0;
    }

    public function getMaxVotingPower(Poll $poll): ?int
    {
        // No theoretical maximum for asset-weighted voting
        return null;
    }

    public function getMinimumBalance(): int
    {
        return self::MIN_BALANCE_FOR_VOTE;
    }

    public function getPowerDivisor(): int
    {
        return self::POWER_DIVISOR;
    }
}
