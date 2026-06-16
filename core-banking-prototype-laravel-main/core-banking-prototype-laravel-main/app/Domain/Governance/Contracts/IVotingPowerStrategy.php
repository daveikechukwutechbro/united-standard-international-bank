<?php

declare(strict_types=1);

namespace App\Domain\Governance\Contracts;

use App\Domain\Governance\Models\Poll;
use App\Models\User;

interface IVotingPowerStrategy
{
    /**
     * Calculate voting power for a user in a specific poll.
     */
    public function calculatePower(User $user, Poll $poll): int;

    /**
     * Get the strategy name.
     */
    public function getName(): string;

    /**
     * Get strategy description.
     */
    public function getDescription(): string;

    /**
     * Validate if user can vote with this strategy.
     */
    public function canVote(User $user, Poll $poll): bool;

    /**
     * Get maximum possible voting power for this strategy.
     */
    public function getMaxVotingPower(Poll $poll): ?int;
}
