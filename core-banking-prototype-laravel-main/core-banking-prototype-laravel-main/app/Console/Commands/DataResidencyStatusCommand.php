<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Compliance\Services\Certification\DataResidencyService;
use App\Domain\Compliance\Services\Certification\GeoRoutingService;
use App\Domain\Compliance\Services\Certification\RegionAwareStorageService;
use Illuminate\Console\Command;

class DataResidencyStatusCommand extends Command
{
    protected $signature = 'data-residency:status
        {--region= : Filter by specific region}
        {--tenant= : Filter by specific tenant}
        {--format=text : Output format (text, json)}
        {--verify-storage : Verify regional storage access}';

    protected $description = 'Show data residency status and configuration';

    public function handle(
        DataResidencyService $residencyService,
        GeoRoutingService $geoRoutingService,
        RegionAwareStorageService $storageService,
    ): int {
        $format = $this->option('format');

        $this->info('Data Residency Status');
        $this->info('=====================');
        $this->newLine();

        // Residency status
        $status = $residencyService->getResidencyStatus();
        $results = ['residency' => $status];

        if ($format === 'text') {
            $enabledColor = $status['enabled'] ? 'green' : 'yellow';
            $this->line("  Enabled: <fg={$enabledColor}>" . ($status['enabled'] ? 'Yes' : 'No') . '</>');
            $this->line("  Default region: {$status['default_region']}");
            $this->line('  Available regions: ' . implode(', ', $status['available_regions']));
            $this->line("  Tenant mappings: {$status['tenant_mappings']}");
            $this->line("  Transfers (30d): {$status['transfers_last_30d']}");
            $this->line("  Cross-region (30d): {$status['cross_region_transfers_last_30d']}");
            $this->newLine();

            if (! empty($status['region_distribution'])) {
                $this->info('Region Distribution:');
                foreach ($status['region_distribution'] as $region => $count) {
                    $this->line("  {$region}: {$count} tenants");
                }
                $this->newLine();
            }
        }

        // Routing configuration
        $routingConfig = $geoRoutingService->getRoutingConfig();
        $results['routing'] = $routingConfig;

        if ($format === 'text') {
            $this->info('Routing Configuration:');
            $this->line('  Resolution order: ' . implode(' -> ', $routingConfig['resolution_order']));
            $this->newLine();
        }

        // Optional storage verification
        if ($this->option('verify-storage')) {
            $this->info('Verifying Regional Storage Access...');
            $storageResults = $storageService->verifyStorageAccess();
            $results['storage'] = $storageResults;

            if ($format === 'text') {
                foreach ($storageResults as $region => $result) {
                    $color = $result['accessible'] ? 'green' : 'red';
                    $status = $result['accessible'] ? 'OK' : 'FAIL';
                    $this->line("  {$region} ({$result['disk']}): <fg={$color}>{$status}</>");
                }
                $this->newLine();
            }
        }

        // Tenant-specific info
        $tenant = $this->option('tenant');
        if ($tenant) {
            $tenantRegion = $residencyService->getRegionForTenant($tenant);
            $tenantRegions = $residencyService->getTenantRegions($tenant);
            $results['tenant'] = [
                'tenant_id'      => $tenant,
                'primary_region' => $tenantRegion,
                'all_regions'    => $tenantRegions->pluck('region')->toArray(),
            ];

            if ($format === 'text') {
                $this->info("Tenant {$tenant}:");
                $this->line("  Primary region: {$tenantRegion}");
                $this->line('  All regions: ' . $tenantRegions->pluck('region')->implode(', '));
                $this->newLine();
            }
        }

        if ($format === 'json') {
            $this->line((string) json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $this->info('Data residency status complete.');

        return Command::SUCCESS;
    }
}
