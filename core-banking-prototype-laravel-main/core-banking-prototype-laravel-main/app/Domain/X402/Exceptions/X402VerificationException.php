<?php

declare(strict_types=1);

namespace App\Domain\X402\Exceptions;

use App\Domain\X402\DataObjects\VerifyResponse;
use RuntimeException;
use Throwable;

/**
 * Thrown when an x402 payment verification fails.
 *
 * Carries the machine-readable reason code and human-readable message
 * returned by the facilitator.
 */
class X402VerificationException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $invalidReason,
        public readonly string $invalidMessage,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create an exception from a failed VerifyResponse.
     */
    public static function fromVerifyResponse(VerifyResponse $response): self
    {
        $reason = $response->invalidReason ?? 'unknown';
        $message = $response->invalidMessage ?? 'Payment verification failed.';

        return new self(
            message: "x402 verification failed: {$reason} - {$message}",
            invalidReason: $reason,
            invalidMessage: $message,
        );
    }

    /**
     * Get additional context for logging.
     *
     * @return array{invalidReason: string, invalidMessage: string}
     */
    public function context(): array
    {
        return [
            'invalidReason'  => $this->invalidReason,
            'invalidMessage' => $this->invalidMessage,
        ];
    }
}
