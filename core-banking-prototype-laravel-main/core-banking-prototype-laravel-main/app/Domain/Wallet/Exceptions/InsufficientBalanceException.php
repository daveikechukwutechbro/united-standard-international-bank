<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Exceptions;

/**
 * Exception thrown when a wallet operation fails due to insufficient balance.
 */
class InsufficientBalanceException extends WalletException
{
    public function __construct(
        public readonly string $walletId,
        public readonly string $assetCode,
        public readonly string $requiredAmount,
        public readonly string $availableAmount,
        string $message = ''
    ) {
        if ($message === '') {
            $message = sprintf(
                'Insufficient balance in wallet %s for asset %s. Required: %s, Available: %s',
                $walletId,
                $assetCode,
                $requiredAmount,
                $availableAmount
            );
        }
        parent::__construct($message);
    }
}
