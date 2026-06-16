<?php

declare(strict_types=1);

namespace App\Domain\Shared\EventSourcing;

use Spatie\EventSourcing\AggregateRoots\Exceptions\InvalidEloquentStoredEventModel;
use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;
use Spatie\EventSourcing\StoredEvents\Repositories\EloquentStoredEventRepository;

/**
 * Base repository for tenant-aware stored events.
 *
 * This repository ensures events are stored in the tenant database
 * using the TenantAwareStoredEvent model or its subclasses.
 * When domain partitioning is active, the EventRouter determines
 * which table to use based on the event's domain namespace.
 *
 * Usage:
 * ```php
 * class TransactionEventRepository extends TenantAwareStoredEventRepository
 * {
 *     public function __construct()
 *     {
 *         parent::__construct(TransactionEvent::class);
 *     }
 * }
 * ```
 */
class TenantAwareStoredEventRepository extends EloquentStoredEventRepository
{
    /**
     * @throws InvalidEloquentStoredEventModel
     */
    public function __construct(
        protected string $storedEventModel = TenantAwareStoredEvent::class
    ) {
        if (! is_subclass_of($this->storedEventModel, EloquentStoredEvent::class)) {
            throw new InvalidEloquentStoredEventModel(
                "The class {$this->storedEventModel} must extend EloquentStoredEvent"
            );
        }
    }

    /**
     * Resolve the event table using the EventRouter when domain partitioning is active.
     */
    public function resolveEventTable(string $eventClass): ?string
    {
        if (config('event-store.partitioning.strategy') !== 'domain') {
            return null;
        }

        /** @var EventRouterInterface|null $router */
        $router = app(EventRouterInterface::class);

        if (! $router) {
            return null;
        }

        return $router->resolveTableForEvent($eventClass);
    }
}
