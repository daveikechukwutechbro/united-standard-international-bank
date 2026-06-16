<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Contracts;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiProtocol;

interface LendingProtocolInterface
{
    public function getProtocol(): DeFiProtocol;

    /**
     * Supply (deposit) an asset to earn interest.
     *
     * @return array{tx_hash: string, supplied_amount: string, atoken_received: string}
     */
    public function supply(
        CrossChainNetwork $chain,
        string $token,
        string $amount,
        string $walletAddress,
    ): array;

    /**
     * Borrow an asset against collateral.
     *
     * @return array{tx_hash: string, borrowed_amount: string, health_factor: string}
     */
    public function borrow(
        CrossChainNetwork $chain,
        string $token,
        string $amount,
        string $walletAddress,
    ): array;

    /**
     * Repay a borrowed asset.
     *
     * @return array{tx_hash: string, repaid_amount: string, remaining_debt: string}
     */
    public function repay(
        CrossChainNetwork $chain,
        string $token,
        string $amount,
        string $walletAddress,
    ): array;

    /**
     * Withdraw a supplied asset.
     *
     * @return array{tx_hash: string, withdrawn_amount: string}
     */
    public function withdraw(
        CrossChainNetwork $chain,
        string $token,
        string $amount,
        string $walletAddress,
    ): array;

    /**
     * Get available lending markets.
     *
     * @return array<array{token: string, supply_apy: string, borrow_apy: string, total_supplied: string, total_borrowed: string, ltv: string}>
     */
    public function getMarkets(CrossChainNetwork $chain): array;

    /**
     * Get user positions in lending markets.
     *
     * @return array{supplies: array<array<string, mixed>>, borrows: array<array<string, mixed>>, health_factor: string, net_apy: string}
     */
    public function getUserPositions(CrossChainNetwork $chain, string $walletAddress): array;
}
