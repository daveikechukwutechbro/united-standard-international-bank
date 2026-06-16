<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Plugin;

use App\Domain\Shared\Models\Plugin;

class PluginQuery
{
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     */
    public function __invoke($_, array $args): ?Plugin
    {
        /** @var Plugin|null */
        return Plugin::find($args['id']);
    }
}
