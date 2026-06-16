<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\X402;

use App\Domain\X402\Models\X402SpendingLimit;

class DeleteX402SpendingLimitMutation
{
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     */
    public function __invoke($_, array $args): bool
    {
        $teamId = auth()->user()?->currentTeam?->id;

        $limit = X402SpendingLimit::where('agent_id', $args['agent_id'])
            ->where('team_id', $teamId)
            ->firstOrFail();
        $limit->delete();

        return true;
    }
}
