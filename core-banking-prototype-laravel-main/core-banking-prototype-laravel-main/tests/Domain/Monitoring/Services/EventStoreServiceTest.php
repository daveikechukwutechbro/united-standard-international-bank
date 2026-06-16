<?php

declare(strict_types=1);

use App\Domain\Monitoring\Services\EventStoreService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Cache::flush();
});

describe('EventStoreService', function () {
    it('returns domain table map with expected domains', function () {
        $service = new EventStoreService();
        $map = $service->getDomainTableMap();

        expect($map)->toBeArray();
        expect($map)->toHaveKey('Account');
        expect($map)->toHaveKey('Stablecoin');
        expect($map)->toHaveKey('Treasury');
        expect($map)->toHaveKey('Monitoring');
        expect($map)->toHaveKey('Compliance');
        expect($map)->toHaveKey('Exchange');
        expect($map)->toHaveKey('Wallet');

        // Each entry has expected structure
        foreach ($map as $domain => $tables) {
            expect($tables)->toHaveKey('event_table');
            expect($tables)->toHaveKey('snapshot_table');
            expect($tables['event_table'])->toBeString();
        }
    });

    it('returns table stats for existing tables', function () {
        $service = new EventStoreService();
        $stats = $service->getTableStats('stored_events');

        expect($stats)->toBeArray();
        expect($stats['table'])->toBe('stored_events');
        expect($stats['exists'])->toBeTrue();
        expect($stats)->toHaveKey('total_events');
        expect($stats)->toHaveKey('unique_aggregates');
        expect($stats)->toHaveKey('oldest_event');
        expect($stats)->toHaveKey('newest_event');
        expect($stats)->toHaveKey('event_class_distribution');
    });

    it('returns graceful response for non-existent tables', function () {
        $service = new EventStoreService();
        $stats = $service->getTableStats('non_existent_table');

        expect($stats)->toBeArray();
        expect($stats['exists'])->toBeFalse();
        expect($stats['total_events'])->toBe(0);
    });

    it('gets all stats with summary', function () {
        $service = new EventStoreService();
        $allStats = $service->getAllStats();

        expect($allStats)->toBeArray();
        expect($allStats)->toHaveKey('summary');
        expect($allStats['summary'])->toHaveKey('total_events');
        expect($allStats['summary'])->toHaveKey('total_aggregates');
        expect($allStats['summary'])->toHaveKey('total_snapshots');
        expect($allStats['summary'])->toHaveKey('events_today');
        expect($allStats['summary'])->toHaveKey('domain_count');
    });

    it('counts events with date range filter', function () {
        $service = new EventStoreService();

        $count = $service->countEvents('stored_events');
        expect($count)->toBeInt();
        expect($count)->toBeGreaterThanOrEqual(0);

        // With date range
        $countFiltered = $service->countEvents(
            'stored_events',
            now()->subDays(7)->toDateTimeString(),
            now()->toDateTimeString(),
        );
        expect($countFiltered)->toBeInt();
        expect($countFiltered)->toBeGreaterThanOrEqual(0);
    });

    it('returns zero for non-existent table counts', function () {
        $service = new EventStoreService();
        $count = $service->countEvents('non_existent_table');

        expect($count)->toBe(0);
    });

    it('gets snapshot stats', function () {
        $service = new EventStoreService();

        // Test with an actual snapshot table that exists
        if (Schema::hasTable('snapshots')) {
            $stats = $service->getSnapshotStats('snapshots');
            expect($stats['exists'])->toBeTrue();
            expect($stats)->toHaveKey('total_snapshots');
            expect($stats)->toHaveKey('unique_aggregates');
        }

        // Test with non-existent table
        $stats = $service->getSnapshotStats('non_existent_snapshots');
        expect($stats['exists'])->toBeFalse();
    });

    it('discovers event tables', function () {
        $service = new EventStoreService();
        $tables = $service->discoverEventTables();

        expect($tables)->toBeArray();
        expect($tables)->toContain('stored_events');
    });

    it('resolves event table for known domain', function () {
        $service = new EventStoreService();

        expect($service->resolveEventTable('Account'))->toBe('stored_events');
        expect($service->resolveEventTable('Stablecoin'))->toBe('stored_events');
        expect($service->resolveEventTable('NonExistent'))->toBeNull();
    });

    it('resolves snapshot table for known domain', function () {
        $service = new EventStoreService();

        expect($service->resolveSnapshotTable('Account'))->toBe('transaction_snapshots');
        expect($service->resolveSnapshotTable('Treasury'))->toBe('treasury_snapshots');
        expect($service->resolveSnapshotTable('Exchange'))->toBeNull();
        expect($service->resolveSnapshotTable('NonExistent'))->toBeNull();
    });

    it('cleans up snapshots for non-existent table returns zero', function () {
        $service = new EventStoreService();
        $deleted = $service->cleanupSnapshots('non_existent_table', 30);

        expect($deleted)->toBe(0);
    });

    it('gets per-domain event counts', function () {
        $service = new EventStoreService();
        $counts = $service->getPerDomainEventCounts();

        expect($counts)->toBeArray();
    });

    it('gets event throughput', function () {
        $service = new EventStoreService();
        $throughput = $service->getEventThroughput('stored_events', 60);

        expect($throughput)->toBeArray();
    });

    it('returns empty throughput for non-existent table', function () {
        $service = new EventStoreService();
        $throughput = $service->getEventThroughput('non_existent_table', 60);

        expect($throughput)->toBeEmpty();
    });

    it('caches all stats results', function () {
        $service = new EventStoreService();

        // First call populates cache
        $stats1 = $service->getAllStats();

        // Verify cache was set
        expect(Cache::has('event_store:all_stats'))->toBeTrue();

        // Second call should return cached result
        $stats2 = $service->getAllStats();
        expect($stats2)->toEqual($stats1);
    });
});
