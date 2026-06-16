<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Basket;

use App\Domain\Basket\Models\BasketAsset;
use App\Domain\Basket\Services\BasketRebalancingService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class RebalanceBasketMutation
{
    public function __construct(
        private readonly BasketRebalancingService $rebalancingService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): BasketAsset
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        /** @var BasketAsset|null $basket */
        $basket = BasketAsset::query()->find($args['id']);

        if (! $basket) {
            throw new ModelNotFoundException('Basket not found.');
        }

        $this->rebalancingService->rebalanceIfNeeded($basket);

        return $basket->fresh() ?? $basket;
    }
}
