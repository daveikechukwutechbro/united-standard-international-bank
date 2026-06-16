<?php

declare(strict_types=1);

namespace App\Domain\X402\Exceptions;

use App\Domain\X402\DataObjects\SettleResponse;
use RuntimeException;
use Throwable;

/**
 * Thrown when an x402 payment settlement fails.
 *
 * Carries the machine-readable error reason and human-readable message
 * returned by the facilitator after an on-chain settlement attempt.
 */
class X402SettlementException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorReason,
        public readonly string $errorMessage,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create an exception from a failed SettleResponse.
     */
    public static function fromSettleResponse(SettleResponse $response): self
    {
        $reason = $response->errorReason ?? 'unknown';
        $message = $response->errorMessage ?? 'Payment settlement failed.';

        return new self(
            message: "x402 settlement failed: {$reason} - {$message}",
            errorReason: $reason,
            errorMessage: $message,
        );
    }

    /**
     * Get additional context for logging.
     *
     * @return array{errorReason: string, errorMessage: string}
     */
    public function context(): array
    {
        return [
            'errorReason'  => $this->errorReason,
            'errorMessage' => $this->errorMessage,
        ];
    }
}
