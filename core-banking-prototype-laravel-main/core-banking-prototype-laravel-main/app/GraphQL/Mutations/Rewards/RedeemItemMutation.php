<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Rewards;

use App\Domain\Rewards\Services\RewardsService;
use GraphQL\Error\Error;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

final class RedeemItemMutation
{
    public function __construct(
        private readonly RewardsService $rewardsService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        try {
            return $this->rewardsService->redeemItem($user, $args['item_id']);
        } catch (RuntimeException $e) {
            throw new Error($e->getMessage());
        }
    }
}
