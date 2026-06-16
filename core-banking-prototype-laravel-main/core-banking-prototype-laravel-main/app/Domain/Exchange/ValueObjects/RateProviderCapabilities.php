<?php

declare(strict_types=1);

namespace App\Domain\Exchange\ValueObjects;

final class RateProviderCapabilities
{
    public function __construct(
        public readonly bool $supportsRealtime = false,
        public readonly bool $supportsHistorical = false,
        public readonly bool $supportsBidAsk = false,
        public readonly bool $supportsVolume = false,
        public readonly bool $supportsBulkQueries = false,
        public readonly bool $requiresAuthentication = true,
        public readonly int $rateLimitPerMinute = 60,
        public readonly array $supportedAssetTypes = ['fiat', 'crypto'],
        public readonly ?int $maxHistoricalDays = null,
        public readonly ?array $additionalFeatures = []
    ) {
    }

    /**
     * Check if provider supports an asset type.
     */
    public function supportsAssetType(string $type): bool
    {
        return in_array($type, $this->supportedAssetTypes);
    }

    /**
     * Check if provider has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->additionalFeatures);
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'supports_realtime'       => $this->supportsRealtime,
            'supports_historical'     => $this->supportsHistorical,
            'supports_bid_ask'        => $this->supportsBidAsk,
            'supports_volume'         => $this->supportsVolume,
            'supports_bulk_queries'   => $this->supportsBulkQueries,
            'requires_authentication' => $this->requiresAuthentication,
            'rate_limit_per_minute'   => $this->rateLimitPerMinute,
            'supported_asset_types'   => $this->supportedAssetTypes,
            'max_historical_days'     => $this->maxHistoricalDays,
            'additional_features'     => $this->additionalFeatures,
        ];
    }
}
