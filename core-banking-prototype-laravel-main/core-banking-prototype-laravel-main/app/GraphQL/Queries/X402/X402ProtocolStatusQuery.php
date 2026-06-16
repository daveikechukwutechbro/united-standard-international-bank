<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\X402;

class X402ProtocolStatusQuery
{
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke($_, array $args): array
    {
        return [
            'enabled'           => (bool) config('x402.enabled', false),
            'version'           => (int) config('x402.version', 2),
            'protocol'          => 'x402',
            'default_network'   => (string) config('x402.server.default_network', 'eip155:8453'),
            'supported_schemes' => ['exact'],
            'client_enabled'    => (bool) config('x402.client.enabled', false),
        ];
    }
}
