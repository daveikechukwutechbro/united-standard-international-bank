<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Microfinance;

use App\Domain\Microfinance\Models\GroupMember;
use App\Domain\Microfinance\Services\GroupLendingService;

class AddMemberMutation
{
    public function __construct(
        private readonly GroupLendingService $groupLendingService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): GroupMember
    {
        return $this->groupLendingService->addMember(
            groupId: (string) $args['group_id'],
            userId: (int) $args['user_id'],
            role: $args['role'] ?? 'member',
        );
    }
}
