<?php

declare(strict_types=1);

namespace App\Domain\X402\Enums;

/**
 * On-chain asset transfer methods supported by the x402 protocol.
 *
 * - EIP3009: EIP-3009 transferWithAuthorization (gasless USDC transfers).
 * - PERMIT2: Uniswap Permit2 signature-based approvals.
 */
enum AssetTransferMethod: string
{
    case EIP3009 = 'eip3009';
    case PERMIT2 = 'permit2';

    /**
     * Get a human-readable label for this transfer method.
     */
    public function label(): string
    {
        return match ($this) {
            self::EIP3009 => 'EIP-3009 Transfer With Authorization',
            self::PERMIT2 => 'Permit2 Signature Transfer',
        };
    }
}
