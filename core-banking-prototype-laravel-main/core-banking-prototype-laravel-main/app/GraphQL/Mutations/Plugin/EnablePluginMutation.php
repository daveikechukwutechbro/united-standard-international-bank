<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Plugin;

use App\Domain\Shared\Models\Plugin;
use App\Infrastructure\Plugins\PluginManager;

class EnablePluginMutation
{
    public function __construct(
        private readonly PluginManager $pluginManager,
    ) {
    }

    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return array{success: bool, message: string}
     */
    public function __invoke($_, array $args): array
    {
        /** @var Plugin $plugin */
        $plugin = Plugin::findOrFail($args['id']);

        return $this->pluginManager->enable($plugin->vendor, $plugin->name);
    }
}
