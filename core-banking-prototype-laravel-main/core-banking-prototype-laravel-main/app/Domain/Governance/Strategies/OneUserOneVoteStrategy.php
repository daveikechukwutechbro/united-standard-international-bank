<?php

declare(strict_types=1);

namespace App\Domain\Governance\Strategies;

use App\Domain\Governance\Contracts\IVotingPowerStrategy;
use App\Domain\Governance\Models\Poll;
use App\Models\User;

class OneUserOneVoteStrategy implements IVotingPowerStrategy
{
    public function calculatePower(User $user, Poll $poll): int
    {
        return 1;
    }

    public function getName(): string
    {
        return 'one_user_one_vote';
    }

    public function getDescription(): string
    {
        return 'Each user gets exactly one vote regardless of assets or account balance';
    }

    public function canVote(User $user, Poll $poll): bool
    {
        // Basic requirements: user must exist and be active
        return $user->exists();
    }

    public function getMaxVotingPower(Poll $poll): ?int
    {
        return 1;
    }
}
