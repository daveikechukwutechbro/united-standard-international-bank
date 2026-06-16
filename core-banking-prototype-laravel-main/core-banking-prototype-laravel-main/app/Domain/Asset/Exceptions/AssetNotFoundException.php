<?php

declare(strict_types=1);

namespace App\Domain\Asset\Exceptions;

/**
 * Exception thrown when a requested asset is not found.
 */
class AssetNotFoundException extends AssetException
{
    public function __construct(
        public readonly string $assetCode,
        string $message = ''
    ) {
        if ($message === '') {
            $message = sprintf('Asset with code %s not found', $assetCode);
        }
        parent::__construct($message);
    }
}
