<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Exceptions;

use Exception;

class HealthFactorTooLowException extends Exception
{
    public const CODE = 'ERR_DEFI_003';

    public function __construct(
        string $message,
        public readonly string $errorCode = self::CODE,
        public readonly int $httpStatusCode = 422,
    ) {
        parent::__construct($message);
    }

    public static function belowThreshold(string $healthFactor, string $threshold = '1.0'): self
    {
        return new self(
            "Health factor {$healthFactor} is below minimum threshold {$threshold}",
        );
    }
}
