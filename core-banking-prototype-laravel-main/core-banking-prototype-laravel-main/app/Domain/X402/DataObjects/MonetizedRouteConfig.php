<?php

declare(strict_types=1);

namespace App\Domain\X402\DataObjects;

/**
 * Configuration for a single monetized API route.
 *
 * Defines the HTTP method, path, pricing, network, and asset details
 * used by the x402 middleware to construct 402 Payment Required responses.
 *
 * Amounts are expressed in USD (e.g. "0.01" for one cent).
 */
readonly class MonetizedRouteConfig
{
    /**
     * @param string $method      HTTP method (GET, POST, etc.).
     * @param string $path        Route path (e.g. "/api/v1/premium/data").
     * @param string $price       Price in USD (e.g. "0.01" for one cent).
     * @param string $network     CAIP-2 network identifier (e.g. "eip155:8453").
     * @param string $asset       Asset symbol or contract address.
     * @param string $scheme      Payment scheme (exact | upto).
     * @param string $description Human-readable description of the resource.
     * @param string $mimeType    MIME type of the response payload.
     * @param array<string, mixed> $extra  Protocol-specific extensions.
     */
    public function __construct(
        public string $method,
        public string $path,
        public string $price,
        public string $network,
        public string $asset = 'USDC',
        public string $scheme = 'exact',
        public string $description = '',
        public string $mimeType = 'application/json',
        public array $extra = [],
    ) {
    }
}
