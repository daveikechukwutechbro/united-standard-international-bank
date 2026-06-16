<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Exceptions;

use Exception;
use Throwable;

class BridgeTransactionFailedException extends Exception
{
    public const CODE = 'ERR_CROSSCHAIN_002';

    public function __construct(
        string $message,
        public readonly string $errorCode = self::CODE,
        public readonly int $httpStatusCode = 500,
        public readonly ?string $transactionId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function executionFailed(string $transactionId, string $reason, ?Throwable $previous = null): self
    {
        return new self(
            "Bridge transaction {$transactionId} failed: {$reason}",
            self::CODE,
            500,
            $transactionId,
            $previous,
        );
    }

    public static function quoteExpired(string $quoteId): self
    {
        return new self(
            "Bridge quote {$quoteId} has expired",
            'ERR_CROSSCHAIN_003',
            422,
        );
    }

    public static function timeout(string $transactionId): self
    {
        return new self(
            "Bridge transaction {$transactionId} timed out",
            'ERR_CROSSCHAIN_004',
            504,
            $transactionId,
        );
    }
}
