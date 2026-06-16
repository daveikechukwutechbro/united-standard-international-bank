<?php

declare(strict_types=1);

namespace App\Domain\Asset\Exceptions;

/**
 * Exception thrown when an asset operation fails due to insufficient balance.
 */
class InsufficientAssetBalanceException extends AssetException
{
    public function __construct(
        public readonly string $accountId,
        public readonly string $assetCode,
        public readonly string $requiredAmount,
        public readonly string $availableAmount,
        string $message = ''
    ) {
        if ($message === '') {
            $message = sprintf(
                'Insufficient balance for asset %s in account %s. Required: %s, Available: %s',
                $assetCode,
                $accountId,
                $requiredAmount,
                $availableAmount
            );
        }
        parent::__construct($message);
    }
}
