<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Microfinance;

use App\Domain\Microfinance\Models\Group;
use App\Domain\Microfinance\Services\GroupLendingService;

class CreateGroupMutation
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
        return $this->groupLendingService->createGroup(
            name: $args['name'],
            meetingFrequency: $args['meeting_frequency'],
            centerName: $args['center_name'] ?? null,
            meetingDay: $args['meeting_day'] ?? null,
        );
    }
}
