<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\CardIssuance;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class CardQuery
{
    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        // VirtualCard is a value object, not an Eloquent model.
        // Return a placeholder structure; actual card retrieval
        // requires the card issuer adapter integration.
        return [
            'id'              => $args['id'],
            'card_token'      => '',
            'cardholder_name' => '',
            'last_four'       => '',
            'network'         => '',
            'status'          => 'unknown',
            'expires_at'      => null,
            'created_at'      => now()->toDateTimeString(),
        ];
    }
}
