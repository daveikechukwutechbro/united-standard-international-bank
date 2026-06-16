<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Rewards;

use App\Domain\Rewards\Services\RewardsService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class QuestsQuery
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

        $quests = $this->rewardsService->getQuests($user);

        if (isset($args['category'])) {
            $quests = $quests->filter(fn (array $quest) => $quest['category'] === $args['category']);
        }

        return $quests->values()->all();
    }
}
