<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Exceptions;

/**
 * Exception thrown when a fund lock is not found.
 */
class LockNotFoundException extends WalletException
{
    public function __construct(
        public readonly string $lockId,
        string $message = ''
    ) {
        if ($message === '') {
            $message = sprintf('Fund lock with ID %s not found', $lockId);
        }
        parent::__construct($message);
    }
}
