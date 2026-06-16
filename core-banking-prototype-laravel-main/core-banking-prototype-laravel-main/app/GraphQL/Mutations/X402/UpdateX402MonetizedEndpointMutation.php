<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\X402;

use App\Domain\X402\Models\X402MonetizedEndpoint;
use Illuminate\Support\Facades\Cache;

class UpdateX402MonetizedEndpointMutation
{
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     */
    public function __invoke($_, array $args): X402MonetizedEndpoint
    {
        $teamId = auth()->user()?->currentTeam?->id;

        /** @var X402MonetizedEndpoint $endpoint */
        $endpoint = X402MonetizedEndpoint::where('team_id', $teamId)->findOrFail($args['id']);

        $oldCacheKey = "x402:route:{$endpoint->method}:{$endpoint->path}";

        $updateData = [];
        foreach (['price', 'network', 'description', 'is_active', 'asset', 'scheme', 'mime_type'] as $field) {
            if (array_key_exists($field, $args)) {
                $updateData[$field] = $args[$field];
            }
        }

        $endpoint->update($updateData);

        Cache::forget($oldCacheKey);
        Cache::forget("x402:route:{$endpoint->method}:{$endpoint->path}");

        /** @var X402MonetizedEndpoint */
        return $endpoint->fresh();
    }
}
