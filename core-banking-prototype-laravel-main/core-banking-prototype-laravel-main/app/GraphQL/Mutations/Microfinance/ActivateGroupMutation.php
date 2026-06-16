<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Microfinance;

use App\Domain\Microfinance\Models\Group;
use App\Domain\Microfinance\Services\GroupLendingService;

class ActivateGroupMutation
{
    public function __construct(
        private readonly GroupLendingService $groupLendingService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Group
    {
        return $this->groupLendingService->activateGroup((string) $args['id']);
    }
}
