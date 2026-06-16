<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Microfinance;

use App\Domain\Microfinance\Enums\GroupStatus;
use App\Domain\Microfinance\Models\Group;
use Illuminate\Database\Eloquent\Builder;

class GroupsQuery
{
    /**
     * @param  array<string, mixed>  $args
     * @return Builder<Group>
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        $query = Group::query()->orderBy('created_at', 'desc');

        if (isset($args['status'])) {
            $status = GroupStatus::from($args['status']);
            $query->where('status', $status->value);
        } else {
            $query->where('status', GroupStatus::ACTIVE->value);
        }

        return $query;
    }
}
