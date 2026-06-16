<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Adapters;

use App\Domain\Compliance\Contracts\SanctionsScreeningInterface;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Internal (simulated) sanctions screening adapter.
 *
 * Provides demo/fallback sanctions screening using keyword-based matching
 * against simulated OFAC, EU, and UN sanctions lists. Used when no external
 * provider (e.g., Chainalysis) is configured.
 */
class InternalSanctionsAdapter implements SanctionsScreeningInterface
{
    /**
     * {@inheritDoc}
     */
    public function screenIndividual(array $searchParams): array
    {
        $results = [
            'matches'       => [],
            'lists_checked' => [],
            'total_matches' => 0,
        ];

        // Check OFAC SDN List
        $ofacResults = $this->checkOFACList($searchParams);
        if (! empty($ofacResults)) {
            $results['matches']['OFAC'] = $ofacResults;
            $results['total_matches'] += count($ofacResults);
        }
        $results['lists_checked'][] = 'OFAC';

        // Check EU Sanctions
        $euResults = $this->checkEUSanctions($searchParams);
        if (! empty($euResults)) {
            $results['matches']['EU'] = $euResults;
            $results['total_matches'] += count($euResults);
        }
        $results['lists_checked'][] = 'EU';

        // Check UN Sanctions
        $unResults = $this->checkUNSanctions($searchParams);
        if (! empty($unResults)) {
            $results['matches']['UN'] = $unResults;
            $results['total_matches'] += count($unResults);
        }
        $results['lists_checked'][] = 'UN';

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function screenAddress(string $address, string $chain): array
    {
        // Internal adapter does not support blockchain address screening
        return [
            'matches'       => [],
            'lists_checked' => ['Internal'],
            'total_matches' => 0,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'Internal';
    }

    /**
     * Check OFAC SDN List (simulated).
     *
     * @param  array<string, mixed>  $searchParams
     * @return array<int, array<string, mixed>>
     */
    protected function checkOFACList(array $searchParams): array
    {
        $matches = [];
        $name = $searchParams['name'] ?? '';

        try {
            // In real implementation:
            // $response = Http::get($this->sanctionsLists['OFAC'] . 'search', [
            //     'name' => $name,
            //     'fuzzy' => true,
            // ]);

            // Simulated response
            if (str_contains(strtolower($name), 'test') || str_contains(strtolower($name), 'sanctioned')) {
                $matches[] = [
                    'sdn_id'      => '12345',
                    'name'        => $name,
                    'match_score' => 92,
                    'type'        => 'Individual',
                    'program'     => 'CYBER2',
                    'remarks'     => 'Added to SDN list on 2023-01-01',
                ];
            }
        } catch (Exception $e) {
            Log::error('OFAC check failed', ['error' => $e->getMessage()]);
        }

        return $matches;
    }

    /**
     * Check EU Sanctions (simulated).
     *
     * @param  array<string, mixed>  $searchParams
     * @return array<int, array<string, mixed>>
     */
    protected function checkEUSanctions(array $searchParams): array
    {
        // Simulate EU sanctions check
        return [];
    }

    /**
     * Check UN Sanctions (simulated).
     *
     * @param  array<string, mixed>  $searchParams
     * @return array<int, array<string, mixed>>
     */
    protected function checkUNSanctions(array $searchParams): array
    {
        // Simulate UN sanctions check
        return [];
    }
}
