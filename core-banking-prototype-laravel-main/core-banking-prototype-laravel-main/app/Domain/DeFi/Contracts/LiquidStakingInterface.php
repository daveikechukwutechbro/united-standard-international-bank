<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Contracts;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Enums\DeFiProtocol;

interface LiquidStakingInterface
{
    public function getProtocol(): DeFiProtocol;

    /**
     * Stake native tokens and receive liquid staking derivatives.
     *
     * @return array{tx_hash: string, staked_amount: string, derivative_received: string, derivative_token: string}
     */
    public function stake(
        CrossChainNetwork $chain,
        string $amount,
        string $walletAddress,
    ): array;

    /**
     * Unstake liquid staking derivatives.
     *
     * @return array{tx_hash: string, unstaked_amount: string, estimated_completion: string}
     */
    public function unstake(
        CrossChainNetwork $chain,
        string $amount,
        string $walletAddress,
    ): array;

    /**
     * Get current staking APY.
     */
    public function getStakingAPY(CrossChainNetwork $chain): string;

    /**
     * Get user's staked balance.
     *
     * @return array{staked: string, derivative_balance: string, derivative_token: string, value_usd: string}
     */
    public function getStakedBalance(CrossChainNetwork $chain, string $walletAddress): array;
}
