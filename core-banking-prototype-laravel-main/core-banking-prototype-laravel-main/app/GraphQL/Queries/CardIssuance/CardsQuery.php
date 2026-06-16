<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\CardIssuance;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class CardsQuery
{
    /**
     * @param  array<string, mixed>  $args
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        // VirtualCard is a value object, not an Eloquent model.
        // Return empty collection; card listing requires issuer adapter integration.
        return [];
    }
}
