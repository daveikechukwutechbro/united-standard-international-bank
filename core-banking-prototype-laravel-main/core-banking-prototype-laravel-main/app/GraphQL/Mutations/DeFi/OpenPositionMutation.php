<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\DeFi;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiPositionType;
use App\Domain\DeFi\Enums\DeFiProtocol;
use App\Domain\DeFi\Models\DeFiPosition;
use App\Domain\DeFi\Services\DeFiPositionTrackerService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class OpenPositionMutation
{
    public function __construct(
        private readonly DeFiPositionTrackerService $positionTracker,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): DeFiPosition
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $protocol = DeFiProtocol::from($args['protocol']);
        $type = DeFiPositionType::from($args['type']);
        $chain = CrossChainNetwork::from($args['chain']);

        $positionVO = $this->positionTracker->openPosition(
            protocol: $protocol,
            type: $type,
            chain: $chain,
            asset: $args['asset'],
            amount: $args['amount'],
            valueUsd: $args['value_usd'] ?? $args['amount'],
            apy: $args['apy'] ?? '0',
            walletAddress: $args['wallet_address'] ?? '',
            healthFactor: $args['health_factor'] ?? null,
        );

        // Create the read-model record from the value object for GraphQL response.
        return DeFiPosition::create([
            'user_id'       => $user->id,
            'position_id'   => $positionVO->positionId,
            'protocol'      => $protocol->value,
            'type'          => $type->value,
            'chain'         => $chain->value,
            'asset'         => $args['asset'],
            'amount'        => $args['amount'],
            'value_usd'     => $args['value_usd'] ?? $args['amount'],
            'apy'           => $args['apy'] ?? '0',
            'health_factor' => $args['health_factor'] ?? null,
            'status'        => 'active',
            'opened_at'     => now(),
        ]);
    }
}
