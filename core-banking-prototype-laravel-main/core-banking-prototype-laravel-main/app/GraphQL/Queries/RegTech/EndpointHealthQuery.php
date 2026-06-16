<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\RegTech;

use App\Domain\RegTech\Models\RegulatoryEndpoint;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

final class EndpointHealthQuery
{
    /**
     * @param  array<string, mixed>  $args
     * @return Collection<int, RegulatoryEndpoint>
     */
    public function __invoke(mixed $rootValue, array $args): Collection
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return RegulatoryEndpoint::query()
            ->where('is_active', true)
            ->orderBy('last_health_check', 'desc')
            ->get();
    }
}
