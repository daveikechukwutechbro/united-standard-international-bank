<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Relayer;

use App\Domain\Relayer\Enums\SupportedNetwork;
use App\Domain\Relayer\Services\GasStationService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class EstimateGasQuery
{
    public function __construct(
        private readonly GasStationService $gasStationService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $network = SupportedNetwork::tryFrom($args['network']) ?? SupportedNetwork::POLYGON;

        $estimate = $this->gasStationService->estimateFee($args['data'], $network);

        return [
            'network'                  => $args['network'],
            'estimated_fee'            => (float) ($estimate['estimated_gas'] ?? 0),
            'max_fee_per_gas'          => $estimate['fee_usdc'] ?? null,
            'max_priority_fee_per_gas' => $estimate['fee_usdt'] ?? null,
            'currency'                 => 'USDC',
        ];
    }
}
