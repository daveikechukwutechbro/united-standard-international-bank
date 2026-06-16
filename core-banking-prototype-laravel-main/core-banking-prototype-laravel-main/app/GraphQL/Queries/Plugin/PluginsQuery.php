<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Plugin;

use App\Domain\Shared\Models\Plugin;
use Illuminate\Database\Eloquent\Collection;

class PluginsQuery
{
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return Collection<int, Plugin>
     */
    public function __invoke($_, array $args): Collection
    {
        $query = Plugin::query();

        if (isset($args['status'])) {
            $query->where('status', $args['status']);
        }

        if (isset($args['vendor'])) {
            $query->where('vendor', $args['vendor']);
        }

        return $query->orderBy('vendor')->orderBy('name')->get();
    }
}
