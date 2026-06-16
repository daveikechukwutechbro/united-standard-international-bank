<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\X402;

use App\Domain\X402\Models\X402MonetizedEndpoint;
use Illuminate\Support\Facades\Cache;

class DeleteX402MonetizedEndpointMutation
{
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     */
    public function __invoke($_, array $args): bool
    {
        $teamId = auth()->user()?->currentTeam?->id;

        /** @var X402MonetizedEndpoint $endpoint */
        $endpoint = X402MonetizedEndpoint::where('team_id', $teamId)->findOrFail($args['id']);

        Cache::forget("x402:route:{$endpoint->method}:{$endpoint->path}");

        $endpoint->delete();

        return true;
    }
}
