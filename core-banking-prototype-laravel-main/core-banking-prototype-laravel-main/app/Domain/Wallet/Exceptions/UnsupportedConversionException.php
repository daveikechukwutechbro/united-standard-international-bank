<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Exceptions;

/**
 * Exception thrown when an asset conversion pair is not supported.
 */
class UnsupportedConversionException extends WalletException
{
    public function __construct(
        public readonly string $fromAssetCode,
        public readonly string $toAssetCode,
        string $message = ''
    ) {
        if ($message === '') {
            $message = sprintf(
                'Conversion from %s to %s is not supported',
                $fromAssetCode,
                $toAssetCode
            );
        }
        parent::__construct($message);
    }
}
