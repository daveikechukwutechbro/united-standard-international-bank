<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\X402;

use App\Domain\X402\Models\X402SpendingLimit;
use Illuminate\Pagination\LengthAwarePaginator;

class X402SpendingLimitsQuery
{
    /**
     * Resolve a single spending limit by agent_id or paginated list.
     *
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return X402SpendingLimit|LengthAwarePaginator<int, X402SpendingLimit>|null
     */
    public function __invoke($_, array $args): X402SpendingLimit|LengthAwarePaginator|null
    {
        $teamId = auth()->user()?->currentTeam?->id;

        // Single limit lookup by agent_id
        if (isset($args['agent_id'])) {
            return X402SpendingLimit::where('team_id', $teamId)
                ->where('agent_id', $args['agent_id'])
                ->first();
        }

        // Paginated list
        return X402SpendingLimit::where('team_id', $teamId)
            ->orderBy('agent_id')
            ->paginate($args['first'] ?? 20, ['*'], 'page', $args['page'] ?? 1);
    }
}
