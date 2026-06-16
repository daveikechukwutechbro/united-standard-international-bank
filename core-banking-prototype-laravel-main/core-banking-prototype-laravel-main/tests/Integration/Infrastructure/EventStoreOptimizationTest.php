<?php

declare(strict_types=1);

use App\Domain\Monitoring\Services\EventArchivalService;
use App\Domain\Monitoring\Services\EventStoreHealthCheck;
use App\Domain\Monitoring\Services\EventStoreService;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class);

beforeEach(function () {
    Cache::flush();
});

describe('Event Store Optimization Integration', function () {
    it('event:stats command runs successfully', function () {
        $this->artisan('event:stats')
            ->assertSuccessful();
    });

    it('event:replay dry-run completes', function () {
        $this->artisan('event:replay --dry-run')
            ->expectsOutputToContain('DRY RUN')
            ->assertSuccessful();
    });

    it('snapshot:cleanup dry-run completes', function () {
        $this->artisan('snapshot:cleanup --dry-run')
            ->expectsOutputToContain('DRY RUN')
            ->assertSuccessful();
    });

    it('event:archive dry-run completes', function () {
        $this->artisan('event:archive --dry-run')
            ->expectsOutputToContain('DRY RUN')
            ->assertSuccessful();
    });

    it('event:compact dry-run completes', function () {
        $this->artisan('event:compact --dry-run')
            ->expectsOutputToContain('DRY RUN')
            ->assertSuccessful();
    });

    it('system:health-check --deep completes', function () {
        $this->artisan('system:health-check --deep')
            ->assertSuccessful();
    });

    it('EventStoreService provides consistent data across methods', function () {
        $service = app(EventStoreService::class);

        $domainMap = $service->getDomainTableMap();
        $eventTables = $service->discoverEventTables();
        $allStats = $service->getAllStats();

        expect($domainMap)->toBeArray();
        expect(count($domainMap))->toBeGreaterThan(0);
        expect($eventTables)->toBeArray();
        expect($allStats)->toHaveKey('summary');
    });

    it('EventStoreHealthCheck checkAll returns valid structure', function () {
        $healthCheck = app(EventStoreHealthCheck::class);
        $result = $healthCheck->checkAll();

        expect($result)->toHaveKey('status');
        expect($result)->toHaveKey('healthy');
        expect($result)->toHaveKey('timestamp');
        expect($result)->toHaveKey('checks');
        expect($result['checks'])->toHaveKey('event_table_connectivity');
        expect($result['checks'])->toHaveKey('projector_lag');
        expect($result['checks'])->toHaveKey('snapshot_freshness');
        expect($result['checks'])->toHaveKey('event_growth_rate');
    });

    it('EventArchivalService returns archival stats', function () {
        $service = app(EventArchivalService::class);
        $stats = $service->getArchivalStats();

        expect($stats)->toBeArray();
        expect($stats)->toHaveKey('archived_events');
        expect($stats)->toHaveKey('source_tables');
    });
});
