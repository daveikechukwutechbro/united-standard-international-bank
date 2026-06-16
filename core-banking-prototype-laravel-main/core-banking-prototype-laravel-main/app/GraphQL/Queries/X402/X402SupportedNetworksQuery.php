<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\X402;

use App\Domain\X402\Enums\X402Network;

class X402SupportedNetworksQuery
{
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     * @return array<int, array<string, mixed>>
     */
    public function __invoke($_, array $args): array
    {
        return collect(X402Network::cases())->map(fn (X402Network $n) => [
            'id'            => $n->value,
            'name'          => $n->label(),
            'testnet'       => $n->isTestnet(),
            'chain_id'      => $n->chainId(),
            'usdc_address'  => $n->usdcAddress(),
            'usdc_decimals' => $n->usdcDecimals(),
            'explorer_url'  => $n->explorerUrl(),
        ])->values()->all();
    }
}
