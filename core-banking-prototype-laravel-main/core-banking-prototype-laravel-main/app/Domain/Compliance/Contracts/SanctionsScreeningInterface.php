<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Contracts;

/**
 * Interface for sanctions screening adapters.
 *
 * Provides a unified contract for screening individuals and blockchain
 * addresses against global sanctions lists. Implementations may use
 * internal simulated data or external providers such as Chainalysis.
 */
interface SanctionsScreeningInterface
{
    /**
     * Screen an individual against sanctions lists.
     *
     * @param  array{name?: string, date_of_birth?: string, nationality?: string, id_number?: string}  $searchParams
     * @return array{matches: array<string, array<int, array<string, mixed>>>, lists_checked: list<string>, total_matches: int}
     */
    public function screenIndividual(array $searchParams): array;

    /**
     * Screen a blockchain address against sanctions lists.
     *
     * @param  string  $address  The blockchain address to screen.
     * @param  string  $chain    The blockchain network (e.g., 'bitcoin', 'ethereum').
     * @return array{matches: array<string, array<int, array<string, mixed>>>, lists_checked: list<string>, total_matches: int}
     */
    public function screenAddress(string $address, string $chain): array;

    /**
     * Get the display name of this screening provider.
     */
    public function getName(): string;
}
