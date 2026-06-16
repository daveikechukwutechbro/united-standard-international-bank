<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Plugin;

use App\Domain\Shared\Models\Plugin;
use App\Infrastructure\Plugins\PluginHookManager;

class PluginMarketplaceStatsQuery
{
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return array<string, int>
     */
    public function __invoke($_, array $args): array
    {
        return [
            'total'            => Plugin::count(),
            'active'           => Plugin::where('status', 'active')->count(),
            'inactive'         => Plugin::where('status', 'inactive')->count(),
            'failed'           => Plugin::where('status', 'failed')->count(),
            'hook_point_count' => count(PluginHookManager::HOOK_POINTS),
        ];
    }
}
