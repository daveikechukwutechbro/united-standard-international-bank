<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Services\Certification;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class GeoRoutingService
{
    /**
     * Resolve region from request context.
     */
    public function resolveRegion(Request $request): string
    {
        // 1. Check explicit header
        $headerRegion = $request->header('X-Data-Region');
        if ($headerRegion && $this->isValidRegion($headerRegion)) {
            return strtoupper($headerRegion);
        }

        // 2. Check query parameter
        $queryRegion = $request->query('region');
        if ($queryRegion && $this->isValidRegion($queryRegion)) {
            return strtoupper($queryRegion);
        }

        // 3. Try to resolve from IP geolocation (simplified)
        $ipRegion = $this->resolveFromIp($request->ip());
        if ($ipRegion) {
            return $ipRegion;
        }

        // 4. Fall back to default
        return Config::get('compliance-certification.data_residency.default_region', 'EU');
    }

    /**
     * Check if a region code is valid.
     */
    public function isValidRegion(string $region): bool
    {
        $regions = Config::get('compliance-certification.data_residency.regions', []);

        return isset($regions[strtoupper($region)]);
    }

    /**
     * Get all available regions.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAvailableRegions(): array
    {
        return Config::get('compliance-certification.data_residency.regions', []);
    }

    /**
     * Get region configuration.
     *
     * @return array<string, mixed>|null
     */
    public function getRegionConfig(string $region): ?array
    {
        $regions = Config::get('compliance-certification.data_residency.regions', []);

        return $regions[strtoupper($region)] ?? null;
    }

    /**
     * Resolve region from IP address (simplified mapping).
     * In production, integrate MaxMind GeoIP2 or AWS CloudFront geo headers.
     */
    private function resolveFromIp(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }

        // Private/loopback ranges — cannot resolve
        $privateRanges = ['10.', '127.', '192.168.', '172.'];

        foreach ($privateRanges as $prefix) {
            if (str_starts_with($ip, $prefix)) {
                return null;
            }
        }

        // Simplified continent-level mapping for demo purposes
        // In production, use MaxMind GeoIP2 or similar service
        $regionPrefixes = Config::get('compliance-certification.data_residency.ip_region_map', []);

        foreach ($regionPrefixes as $prefix => $region) {
            if (str_starts_with($ip, (string) $prefix)) {
                return $region;
            }
        }

        return null;
    }

    /**
     * Get routing configuration summary.
     *
     * @return array<string, mixed>
     */
    public function getRoutingConfig(): array
    {
        $regions = $this->getAvailableRegions();
        $enabled = Config::get('compliance-certification.data_residency.enabled', false);

        return [
            'enabled'           => $enabled,
            'available_regions' => array_keys($regions),
            'default_region'    => Config::get('compliance-certification.data_residency.default_region', 'EU'),
            'region_details'    => collect($regions)->map(function ($config, $code) {
                return [
                    'code'         => $code,
                    'display_name' => $config['display_name'] ?? $code,
                    'storage_disk' => $config['storage_disk'] ?? 'local',
                    'timezone'     => $config['timezone'] ?? 'UTC',
                ];
            })->values()->toArray(),
            'resolution_order' => [
                'X-Data-Region header',
                'region query parameter',
                'IP geolocation',
                'Default region',
            ],
        ];
    }
}
