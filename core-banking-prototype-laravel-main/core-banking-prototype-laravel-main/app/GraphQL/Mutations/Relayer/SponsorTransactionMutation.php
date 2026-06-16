<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Relayer;

use App\Domain\Relayer\Enums\SupportedNetwork;
use App\Domain\Relayer\Services\GasStationService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class SponsorTransactionMutation
{
    public function __construct(
        private readonly GasStationService $gasStationService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): string
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $network = SupportedNetwork::tryFrom($args['network']) ?? SupportedNetwork::POLYGON;

        $result = $this->gasStationService->sponsorTransaction(
            userAddress: $args['sender'],
            callData: $args['data'],
            signature: $args['value'] ?? '0x',
            network: $network,
        );

        return json_encode($result) ?: '{}';
    }
}
