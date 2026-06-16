<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Adapters;

use App\Domain\Compliance\Contracts\SanctionsScreeningInterface;
use App\Domain\Compliance\Services\OfacAddressListService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Composite sanctions screening adapter.
 *
 * Chains multiple screening providers for defense-in-depth:
 * 1. Local OFAC SDN address list (instant, always available)
 * 2. GoPlus Security API (free tier, EVM + Solana)
 * 3. Optional external providers (Chainalysis, etc.)
 *
 * Fails open on provider errors — if a provider is unavailable,
 * remaining providers still run. Fails closed on matches — any
 * provider flagging the address produces a sanctioned result.
 */
class CompositeScreeningAdapter implements SanctionsScreeningInterface
{
    /**
     * @param  list<SanctionsScreeningInterface>  $adapters
     */
    public function __construct(
        private readonly OfacAddressListService $ofacList,
        private readonly array $adapters,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function screenIndividual(array $searchParams): array
    {
        $allMatches = [];
        $listsChecked = [];
        $totalMatches = 0;

        foreach ($this->adapters as $adapter) {
            try {
                $result = $adapter->screenIndividual($searchParams);

                foreach ($result['matches'] ?? [] as $list => $matches) {
                    $allMatches[$list] = array_merge($allMatches[$list] ?? [], $matches);
                }

                $listsChecked = array_merge($listsChecked, $result['lists_checked'] ?? []);
                $totalMatches += $result['total_matches'] ?? 0;
            } catch (Throwable $e) {
                Log::warning('Screening adapter failed for individual check', [
                    'adapter' => $adapter->getName(),
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return [
            'matches'       => $allMatches,
            'lists_checked' => array_values(array_unique($listsChecked)),
            'total_matches' => $totalMatches,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function screenAddress(string $address, string $chain = 'ethereum'): array
    {
        $allMatches = [];
        $listsChecked = [];
        $totalMatches = 0;

        // Layer 1: Local OFAC address list (instant, no API call)
        if ($this->ofacList->isSanctioned($address)) {
            $allMatches['OFAC SDN'] = [[
                'flag'        => 'ofac_sanctioned',
                'description' => 'Address appears on OFAC SDN sanctioned addresses list',
                'severity'    => 'critical',
                'address'     => $address,
            ]];
            $totalMatches++;
        }
        $listsChecked[] = 'OFAC SDN';

        // Layer 2+: External adapters (GoPlus, Chainalysis, etc.)
        foreach ($this->adapters as $adapter) {
            try {
                $result = $adapter->screenAddress($address, $chain);

                foreach ($result['matches'] ?? [] as $list => $matches) {
                    $allMatches[$list] = array_merge($allMatches[$list] ?? [], $matches);
                }

                $listsChecked = array_merge($listsChecked, $result['lists_checked'] ?? []);
                $totalMatches += $result['total_matches'] ?? 0;
            } catch (Throwable $e) {
                Log::warning('Screening adapter failed for address check', [
                    'adapter' => $adapter->getName(),
                    'address' => $address,
                    'chain'   => $chain,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return [
            'matches'       => $allMatches,
            'lists_checked' => array_values(array_unique($listsChecked)),
            'total_matches' => $totalMatches,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        $names = array_map(
            fn (SanctionsScreeningInterface $a): string => $a->getName(),
            $this->adapters,
        );

        return 'Composite (OFAC SDN + ' . implode(' + ', $names) . ')';
    }
}
