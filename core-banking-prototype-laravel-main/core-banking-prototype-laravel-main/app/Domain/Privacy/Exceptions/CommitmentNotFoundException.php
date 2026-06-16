<?php

declare(strict_types=1);

namespace App\Domain\Privacy\Exceptions;

use Exception;

/**
 * Exception thrown when a commitment is not found in the Merkle tree.
 */
class CommitmentNotFoundException extends Exception
{
    public const ERROR_CODE = 'ERR_PRIVACY_306';

    public function __construct(
        public readonly string $commitment,
        public readonly string $network,
        string $message = 'Commitment not found in the Merkle tree.',
    ) {
        parent::__construct($message);
    }

    /**
     * Get the HTTP status code for this exception.
     */
    public function getHttpStatusCode(): int
    {
        return 404;
    }

    /**
     * Get the error code for API responses.
     */
    public function getErrorCode(): string
    {
        return self::ERROR_CODE;
    }

    /**
     * Get additional context for logging.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [
            'commitment' => $this->commitment,
            'network'    => $this->network,
            'error_code' => $this->getErrorCode(),
        ];
    }
}
