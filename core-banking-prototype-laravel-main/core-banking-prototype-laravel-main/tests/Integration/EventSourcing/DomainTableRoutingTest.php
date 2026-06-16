<?php

declare(strict_types=1);

use App\Domain\Monitoring\Services\EventPartitioningService;
use App\Domain\Monitoring\Services\EventStoreService;
use App\Domain\Shared\EventSourcing\EventRouter;
use App\Domain\Shared\EventSourcing\EventRouterInterface;

uses(Tests\TestCase::class);

describe('Domain Table Routing Integration', function () {
    it('registers EventRouter in the container', function () {
        $router = app(EventRouterInterface::class);

        expect($router)->toBeInstanceOf(EventRouter::class);
    });

    it('EventStoreService resolves tables via router when partitioning is domain', function () {
        config(['event-store.partitioning.strategy' => 'domain']);

        $router = app(EventRouterInterface::class);
        $service = new EventStoreService($router);
        $map = $service->getDomainTableMap();

        expect($map['Account']['event_table'])->toBe('account_events');
        expect($map['Exchange']['event_table'])->toBe('exchange_events');
        expect($map['Wallet']['event_table'])->toBe('wallet_events');
    });

    it('EventStoreService falls back to stored_events when partitioning is none', function () {
        config(['event-store.partitioning.strategy' => 'none']);

        $router = app(EventRouterInterface::class);
        $service = new EventStoreService($router);
        $map = $service->getDomainTableMap();

        expect($map['Account']['event_table'])->toBe('stored_events');
        expect($map['Exchange']['event_table'])->toBe('stored_events');
    });

    it('EventPartitioningService reports routing config', function () {
        config(['event-store.partitioning.strategy' => 'domain']);

        $router = app(EventRouterInterface::class);
        $service = new EventPartitioningService($router);
        $config = $service->getRoutingConfig();

        expect($config['strategy'])->toBe('domain');
        expect($config['domains'])->toBeGreaterThan(15);
        expect($config['default_table'])->toBe('stored_events');
    });

    it('EventPartitioningService detects active partitioning', function () {
        config(['event-store.partitioning.strategy' => 'domain']);
        $router = app(EventRouterInterface::class);
        $service = new EventPartitioningService($router);
        expect($service->isPartitioningActive())->toBeTrue();

        config(['event-store.partitioning.strategy' => 'none']);
        expect($service->isPartitioningActive())->toBeFalse();
    });

    it('preserves snapshot table associations', function () {
        config(['event-store.partitioning.strategy' => 'domain']);

        $router = app(EventRouterInterface::class);
        $service = new EventStoreService($router);
        $map = $service->getDomainTableMap();

        expect($map['Account']['snapshot_table'])->toBe('transaction_snapshots');
        expect($map['Treasury']['snapshot_table'])->toBe('treasury_snapshots');
        expect($map['Compliance']['snapshot_table'])->toBe('compliance_snapshots');
        expect($map['Exchange']['snapshot_table'])->toBeNull();
    });

    it('resolves event table for domain via EventStoreService', function () {
        config(['event-store.partitioning.strategy' => 'domain']);

        $router = app(EventRouterInterface::class);
        $service = new EventStoreService($router);

        expect($service->resolveEventTable('Account'))->toBe('account_events');
        expect($service->resolveEventTable('Wallet'))->toBe('wallet_events');
    });
});
