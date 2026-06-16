<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Plugin;

use App\Domain\Shared\Models\Plugin;
use App\Infrastructure\Plugins\PluginSecurityScanner;

class ScanPluginMutation
{
    public function __construct(
        private readonly PluginSecurityScanner $securityScanner,
    ) {
    }

    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return array{plugin: string, safe: bool, issue_count: int}
     */
    public function __invoke($_, array $args): array
    {
        /** @var Plugin $plugin */
        $plugin = Plugin::findOrFail($args['id']);
        $result = $this->securityScanner->scan($plugin->path ?? '');

        return [
            'plugin'      => $plugin->getFullName(),
            'safe'        => $result['safe'],
            'issue_count' => count($result['issues']),
        ];
    }
}
