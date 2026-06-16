<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Exceptions;

use Exception;

class SlippageExceededException extends Exception
{
    public const CODE = 'ERR_DEFI_002';

    public function __construct(
        string $message,
        public readonly string $errorCode = self::CODE,
        public readonly int $httpStatusCode = 422,
    ) {
        parent::__construct($message);
    }

    public static function exceeded(string $expected, string $actual, float $tolerance): self
    {
        return new self(
            "Slippage exceeded: expected {$expected}, got {$actual} (tolerance: {$tolerance}%)",
        );
    }
}
