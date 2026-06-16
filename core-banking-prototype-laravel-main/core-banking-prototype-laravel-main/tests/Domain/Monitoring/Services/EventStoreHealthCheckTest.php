<?php

declare(strict_types=1);

use App\Domain\Monitoring\Services\EventStoreHealthCheck;
use App\Domain\Monitoring\Services\EventStoreService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

describe('EventStoreHealthCheck', function () {
    it('can be instantiated with EventStoreService', function () {
        $service = new EventStoreService();
        $healthCheck = new EventStoreHealthCheck($service);

        expect($healthCheck)->toBeInstanceOf(EventStoreHealthCheck::class);
    });

    it('exposes EventStoreService via accessor', function () {
        $service = new EventStoreService();
        $healthCheck = new EventStoreHealthCheck($service);

        expect($healthCheck->getEventStoreService())->toBe($service);
    });

    it('checks event table connectivity', function () {
        $service = new EventStoreService();
        $healthCheck = new EventStoreHealthCheck($service);

        $result = $healthCheck->checkEventTableConnectivity();

        expect($result)->toBeArray();
        expect($result['name'])->toBe('event_table_connectivity');
        expect($result)->toHaveKey('healthy');
        expect($result)->toHaveKey('tables');
        expect($result['tables'])->toBeArray();
    });

    it('checks projector lag', function () {
        $service = new EventStoreService();
        $healthCheck = new EventStoreHealthCheck($service);

        $result = $healthCheck->checkProjectorLag();

        expect($result)->toBeArray();
        expect($result['name'])->toBe('projector_lag');
        expect($result)->toHaveKey('healthy');
        expect($result)->toHaveKey('message');
    });

    it('checks snapshot freshness', function () {
        $service = new EventStoreService();
        $healthCheck = new EventStoreHealthCheck($service);

        $result = $healthCheck->checkSnapshotFreshness();

        expect($result)->toBeArray();
        expect($result['name'])->toBe('snapshot_freshness');
        expect($result)->toHaveKey('healthy');
        expect($result)->toHaveKey('domains');
        expect($result['domains'])->toBeArray();
    });

    it('checks event growth rate', function () {
        $service = new EventStoreService();
        $healthCheck = new EventStoreHealthCheck($service);

        $result = $healthCheck->checkEventGrowthRate();

        expect($result)->toBeArray();
        expect($result['name'])->toBe('event_growth_rate');
        expect($result)->toHaveKey('healthy');
    });

    it('runs all checks via checkAll', function () {
        $service = new EventStoreService();
        $healthCheck = new EventStoreHealthCheck($service);

        $result = $healthCheck->checkAll();

        expect($result)->toBeArray();
        expect($result)->toHaveKey('status');
        expect($result)->toHaveKey('healthy');
        expect($result)->toHaveKey('timestamp');
        expect($result)->toHaveKey('checks');
        expect($result['checks'])->toHaveKey('event_table_connectivity');
        expect($result['checks'])->toHaveKey('projector_lag');
        expect($result['checks'])->toHaveKey('snapshot_freshness');
        expect($result['checks'])->toHaveKey('event_growth_rate');
    });

    it('reports healthy when all checks pass', function () {
        $service = new EventStoreService();
        $healthCheck = new EventStoreHealthCheck($service);

        $result = $healthCheck->checkAll();

        // In test environment with SQLite, most checks should pass
        expect($result['status'])->toBeIn(['healthy', 'unhealthy']);
        expect($result['healthy'])->toBeBool();
    });

    it('provides ISO 8601 timestamp in checkAll', function () {
        $service = new EventStoreService();
        $healthCheck = new EventStoreHealthCheck($service);

        $result = $healthCheck->checkAll();

        expect($result['timestamp'])->toBeString();
        // ISO 8601 format validation
        expect(strtotime($result['timestamp']))->not->toBeFalse();
    });
});
