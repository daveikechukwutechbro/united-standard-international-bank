<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Exceptions;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use Exception;

class UnsupportedBridgeRouteException extends Exception
{
    public const CODE = 'ERR_CROSSCHAIN_001';

    public function __construct(
        string $message,
        public readonly string $errorCode = self::CODE,
        public readonly int $httpStatusCode = 422,
    ) {
        parent::__construct($message);
    }

    public static function forRoute(
        CrossChainNetwork $source,
        CrossChainNetwork $dest,
        string $token,
    ): self {
        return new self(
            "No bridge supports the route {$source->value} -> {$dest->value} for token {$token}",
        );
    }

    public static function noProviders(CrossChainNetwork $source, CrossChainNetwork $dest): self
    {
        return new self(
            "No bridge providers available for {$source->value} -> {$dest->value}",
        );
    }
}
