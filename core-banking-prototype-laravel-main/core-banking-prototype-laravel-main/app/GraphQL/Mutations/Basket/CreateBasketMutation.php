<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Basket;

use App\Domain\Basket\Models\BasketAsset;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateBasketMutation
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): BasketAsset
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $code = 'BSK-' . strtoupper(Str::random(6));

        // Create basket through the model (event sourced via projectors).
        // The BasketService composeBasket workflow handles composition;
        // here we create the basket definition itself.
        /** @var BasketAsset $basket */
        $basket = BasketAsset::create([
            'code'                => $code,
            'name'                => $args['name'],
            'description'         => $args['description'] ?? null,
            'type'                => $args['type'] ?? 'static',
            'rebalance_frequency' => $args['rebalance_frequency'] ?? 'never',
            'is_active'           => true,
            'created_by'          => $user->uuid ?? (string) $user->id,
        ]);

        return $basket;
    }
}
