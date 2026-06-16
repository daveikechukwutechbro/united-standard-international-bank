<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Rewards;

use App\Domain\Rewards\Services\RewardsService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class ShopQuery
{
    public function __construct(
        private readonly RewardsService $rewardsService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $items = $this->rewardsService->getShopItems();

        if (isset($args['category'])) {
            $items = $items->filter(fn (array $item) => $item['category'] === $args['category']);
        }

        return $items->values()->all();
    }
}
