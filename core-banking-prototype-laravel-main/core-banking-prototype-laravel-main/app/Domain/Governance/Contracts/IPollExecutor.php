<?php

declare(strict_types=1);

namespace App\Domain\Governance\Contracts;

use App\Domain\Governance\Models\Poll;
use App\Domain\Governance\ValueObjects\PollResult;

interface IPollExecutor
{
    /**
     * Execute the poll result if it meets criteria.
     */
    public function execute(Poll $poll, PollResult $result): bool;

    /**
     * Check if poll result can be executed.
     */
    public function canExecute(Poll $poll, PollResult $result): bool;

    /**
     * Get execution requirements.
     */
    public function getExecutionRequirements(): array;

    /**
     * Get executor name.
     */
    public function getName(): string;
}
