<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\X402;

use App\Domain\X402\Models\X402MonetizedEndpoint;
use Illuminate\Pagination\LengthAwarePaginator;

class X402MonetizedEndpointsQuery
{
    /**
     * Resolve a single endpoint by ID or paginated list.
     *
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return X402MonetizedEndpoint|LengthAwarePaginator<int, X402MonetizedEndpoint>|null
     */
    public function __invoke($_, array $args): X402MonetizedEndpoint|LengthAwarePaginator|null
    {
        $teamId = auth()->user()?->currentTeam?->id;

        // Single endpoint lookup by ID
        if (isset($args['id'])) {
            /** @var X402MonetizedEndpoint|null */
            return X402MonetizedEndpoint::where('team_id', $teamId)->find($args['id']);
        }

        // Paginated list
        $query = X402MonetizedEndpoint::where('team_id', $teamId);

        if (isset($args['is_active'])) {
            $query->where('is_active', $args['is_active']);
        }

        if (isset($args['network'])) {
            $query->where('network', $args['network']);
        }

        return $query->orderBy('path')
            ->paginate($args['first'] ?? 20, ['*'], 'page', $args['page'] ?? 1);
    }
}
