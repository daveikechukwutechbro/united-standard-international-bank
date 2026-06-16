<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Services\Certification;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RegionAwareStorageService
{
    /**
     * Get the appropriate storage disk for a region.
     */
    public function getDiskForRegion(string $region): string
    {
        $regions = Config::get('compliance-certification.data_residency.regions', []);
        $regionConfig = $regions[strtoupper($region)] ?? $regions[strtolower($region)] ?? null;

        if ($regionConfig && isset($regionConfig['storage_disk'])) {
            return $regionConfig['storage_disk'];
        }

        return Config::get('filesystems.default', 'local');
    }

    /**
     * Get the storage instance for a region.
     */
    public function getStorageForRegion(string $region): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk($this->getDiskForRegion($region));
    }

    /**
     * Store a file in the region-appropriate storage.
     */
    public function storeInRegion(string $region, string $path, string $contents): bool
    {
        return $this->getStorageForRegion($region)->put($path, $contents);
    }

    /**
     * Get a file from region-appropriate storage.
     */
    public function getFromRegion(string $region, string $path): ?string
    {
        return $this->getStorageForRegion($region)->get($path);
    }

    /**
     * Check if a file exists in region-appropriate storage.
     */
    public function existsInRegion(string $region, string $path): bool
    {
        return $this->getStorageForRegion($region)->exists($path);
    }

    /**
     * Delete a file from region-appropriate storage.
     */
    public function deleteFromRegion(string $region, string $path): bool
    {
        return $this->getStorageForRegion($region)->delete($path);
    }

    /**
     * Get all configured regional storage disks.
     *
     * @return array<string, string>
     */
    public function getRegionalDisks(): array
    {
        $regions = Config::get('compliance-certification.data_residency.regions', []);
        $disks = [];

        foreach ($regions as $regionCode => $config) {
            $disks[$regionCode] = $config['storage_disk'] ?? 'local';
        }

        return $disks;
    }

    /**
     * Verify all regional storage disks are accessible.
     *
     * @return array<string, mixed>
     */
    public function verifyStorageAccess(): array
    {
        $disks = $this->getRegionalDisks();
        $results = [];

        foreach ($disks as $region => $diskName) {
            try {
                $disk = Storage::disk($diskName);
                $testPath = ".compliance-storage-test-{$region}";
                $disk->put($testPath, 'test');
                $disk->delete($testPath);
                $results[$region] = [
                    'disk'       => $diskName,
                    'accessible' => true,
                ];
            } catch (Throwable $e) {
                $results[$region] = [
                    'disk'       => $diskName,
                    'accessible' => false,
                    'error'      => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
