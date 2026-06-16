<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Contracts;

use App\Domain\CrossChain\Enums\CrossChainNetwork;

interface YieldVaultInterface
{
    /**
     * Deposit assets into a yield vault.
     *
     * @return array{tx_hash: string, deposited_amount: string, shares_received: string}
     */
    public function deposit(
        CrossChainNetwork $chain,
        string $vaultId,
        string $amount,
        string $walletAddress,
    ): array;

    /**
     * Withdraw assets from a yield vault.
     *
     * @return array{tx_hash: string, withdrawn_amount: string, shares_burned: string}
     */
    public function withdraw(
        CrossChainNetwork $chain,
        string $vaultId,
        string $amount,
        string $walletAddress,
    ): array;

    /**
     * Get current APY for a vault.
     */
    public function getAPY(CrossChainNetwork $chain, string $vaultId): string;

    /**
     * Get vault information.
     *
     * @return array{vault_id: string, token: string, tvl: string, apy: string, strategy: string}
     */
    public function getVaultInfo(CrossChainNetwork $chain, string $vaultId): array;
}
