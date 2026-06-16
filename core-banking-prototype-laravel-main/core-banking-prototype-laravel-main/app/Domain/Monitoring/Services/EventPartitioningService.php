<?php

declare(strict_types=1);

namespace App\Domain\Monitoring\Services;

use App\Domain\Shared\EventSourcing\EventRouterInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Manages domain-based event store partitioning.
 *
 * Provides utilities for inspecting, validating, and reporting on
 * the distribution of events across domain-specific tables.
 */
class EventPartitioningService
{
    public function __construct(
        private readonly EventRouterInterface $eventRouter,
    ) {
    }

    /**
     * Get partitioning status for all domains.
     *
     * @return array<string, array{table: string, exists: bool, count: int}>
     */
    public function getPartitioningStatus(): array
    {
        $status = [];
        $map = $this->eventRouter->getDomainTableMap();

        foreach ($map as $domain => $table) {
            $exists = Schema::hasTable($table);
            $count = $exists ? DB::table($table)->count() : 0;

            $status[$domain] = [
                'table'  => $table,
                'exists' => $exists,
                'count'  => $count,
            ];
        }

        // Also include the default fallback table
        $defaultTable = $this->eventRouter->getDefaultTable();
        $defaultExists = Schema::hasTable($defaultTable);
        $status['_default'] = [
            'table'  => $defaultTable,
            'exists' => $defaultExists,
            'count'  => $defaultExists ? DB::table($defaultTable)->count() : 0,
        ];

        return $status;
    }

    /**
     * Verify that all required domain event tables exist.
     *
     * @return array{missing: string[], existing: string[]}
     */
    public function verifyTables(): array
    {
        $missing = [];
        $existing = [];

        foreach ($this->eventRouter->getDomainTableMap() as $domain => $table) {
            if (Schema::hasTable($table)) {
                $existing[] = $table;
            } else {
                $missing[] = $table;
            }
        }

        return [
            'missing'  => $missing,
            'existing' => $existing,
        ];
    }

    /**
     * Get event distribution across all tables.
     *
     * @return array{total: int, per_table: array<string, int>, per_domain: array<string, int>}
     */
    public function getEventDistribution(): array
    {
        $perTable = [];
        $perDomain = [];
        $total = 0;

        foreach ($this->eventRouter->getDomainTableMap() as $domain => $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $perTable[$table] = ($perTable[$table] ?? 0) + $count;
                $perDomain[$domain] = $count;
                $total += $count;
            }
        }

        $defaultTable = $this->eventRouter->getDefaultTable();
        if (Schema::hasTable($defaultTable)) {
            $defaultCount = DB::table($defaultTable)->count();
            $perTable[$defaultTable] = $defaultCount;
            $total += $defaultCount;
        }

        return [
            'total'      => $total,
            'per_table'  => $perTable,
            'per_domain' => $perDomain,
        ];
    }

    /**
     * Check if domain partitioning is active.
     */
    public function isPartitioningActive(): bool
    {
        return config('event-store.partitioning.strategy') === 'domain';
    }

    /**
     * Get the routing configuration summary.
     *
     * @return array{strategy: string, domains: int, default_table: string, tables: string[]}
     */
    public function getRoutingConfig(): array
    {
        $map = $this->eventRouter->getDomainTableMap();

        return [
            'strategy'      => (string) config('event-store.partitioning.strategy', 'none'),
            'domains'       => count($map),
            'default_table' => $this->eventRouter->getDefaultTable(),
            'tables'        => array_unique(array_values($map)),
        ];
    }
}
