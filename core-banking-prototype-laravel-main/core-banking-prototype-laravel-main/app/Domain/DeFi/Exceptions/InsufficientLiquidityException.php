<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Exceptions;

use Exception;

class InsufficientLiquidityException extends Exception
{
    public const CODE = 'ERR_DEFI_001';

    public function __construct(
        string $message,
        public readonly string $errorCode = self::CODE,
        public readonly int $httpStatusCode = 422,
    ) {
        parent::__construct($message);
    }

    public static function forPair(string $fromToken, string $toToken, string $amount): self
    {
        return new self(
            "Insufficient liquidity to swap {$amount} {$fromToken} to {$toToken}",
        );
    }
}
