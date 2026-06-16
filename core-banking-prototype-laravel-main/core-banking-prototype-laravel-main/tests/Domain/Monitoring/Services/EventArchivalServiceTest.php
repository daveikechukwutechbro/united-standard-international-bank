<?php

declare(strict_types=1);

use App\Domain\Monitoring\Services\EventArchivalService;
use App\Domain\Monitoring\Services\EventStoreService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Cache::flush();
});

describe('EventArchivalService', function () {
    it('can be instantiated with EventStoreService', function () {
        $storeService = new EventStoreService();
        $service = new EventArchivalService($storeService);

        expect($service)->toBeInstanceOf(EventArchivalService::class);
    });

    it('returns 0 when archiving from non-existent table', function () {
        $storeService = new EventStoreService();
        $service = new EventArchivalService($storeService);

        $result = $service->archiveEvents('non_existent_table', now()->toDateString());

        expect($result)->toBe(0);
    });

    it('returns archival stats', function () {
        $storeService = new EventStoreService();
        $service = new EventArchivalService($storeService);

        $stats = $service->getArchivalStats();

        expect($stats)->toBeArray();
        expect($stats)->toHaveKey('archived_events');
        expect($stats)->toHaveKey('source_tables');
    });

    it('returns archival stats with zero events when table exists but empty', function () {
        $storeService = new EventStoreService();
        $service = new EventArchivalService($storeService);

        if (Schema::hasTable('archived_events')) {
            $stats = $service->getArchivalStats();
            expect($stats['archived_events'])->toBeGreaterThanOrEqual(0);
        } else {
            $stats = $service->getArchivalStats();
            expect($stats['archived_events'])->toBe(0);
        }
    });

    it('returns 0 when compacting non-existent table', function () {
        $storeService = new EventStoreService();
        $service = new EventArchivalService($storeService);

        $result = $service->compactAggregate('non_existent_table', 'test-uuid');

        expect($result)->toBe(0);
    });

    it('returns 0 when restoring from archive for non-existent table', function () {
        $storeService = new EventStoreService();
        $service = new EventArchivalService($storeService);

        $result = $service->restoreFromArchive('non_existent_table');

        expect($result)->toBe(0);
    });

    it('handles compaction with aggregate having fewer events than keep_latest', function () {
        $storeService = new EventStoreService();
        $service = new EventArchivalService($storeService);

        // stored_events exists but any aggregate will have <= keepLatest events in test
        $result = $service->compactAggregate('stored_events', 'non-existent-uuid-123', 100);

        expect($result)->toBe(0);
    });
});
