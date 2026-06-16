<?php

declare(strict_types=1);

namespace App\Domain\Monitoring\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Validates event migration data integrity.
 *
 * Ensures events are correctly migrated from the shared stored_events
 * table to domain-specific tables by verifying counts, ordering, and
 * aggregate consistency.
 */
class EventMigrationValidator
{
    /**
     * Validate a completed migration.
     *
     * @return array{valid: bool, checks: array<string, array{passed: bool, details: string}>}
     */
    public function validate(string $sourceTable, string $targetTable, string $domain): array
    {
        $checks = [];

        $checks['target_table_exists'] = $this->checkTableExists($targetTable);
        $checks['count_consistency'] = $this->checkCountConsistency($sourceTable, $targetTable, $domain);
        $checks['ordering_preserved'] = $this->checkOrderingPreserved($targetTable);
        $checks['aggregate_consistency'] = $this->checkAggregateConsistency($sourceTable, $targetTable, $domain);

        $allPassed = collect($checks)->every(fn (array $check) => $check['passed']);

        return [
            'valid'  => $allPassed,
            'checks' => $checks,
        ];
    }

    /**
     * @return array{passed: bool, details: string}
     */
    private function checkTableExists(string $table): array
    {
        $exists = Schema::hasTable($table);

        return [
            'passed'  => $exists,
            'details' => $exists ? "Table '{$table}' exists" : "Table '{$table}' does not exist",
        ];
    }

    /**
     * @return array{passed: bool, details: string}
     */
    private function checkCountConsistency(string $sourceTable, string $targetTable, string $domain): array
    {
        if (! Schema::hasTable($sourceTable) || ! Schema::hasTable($targetTable)) {
            return ['passed' => false, 'details' => 'One or both tables do not exist'];
        }

        $eventClassMap = config('event-sourcing.event_class_map', []);
        $domainAliases = [];

        foreach ($eventClassMap as $alias => $className) {
            if (preg_match('/App\\\\Domain\\\\' . preg_quote($domain, '/') . '\\\\/', $className)) {
                $domainAliases[] = $alias;
            }
        }

        $sourceCount = DB::table($sourceTable)
            ->whereIn('event_class', $domainAliases)
            ->count();

        $targetCount = DB::table($targetTable)->count();

        $passed = $targetCount >= $sourceCount;

        return [
            'passed'  => $passed,
            'details' => "Source: {$sourceCount}, Target: {$targetCount}",
        ];
    }

    /**
     * @return array{passed: bool, details: string}
     */
    private function checkOrderingPreserved(string $table): array
    {
        if (! Schema::hasTable($table)) {
            return ['passed' => false, 'details' => 'Table does not exist'];
        }

        $count = DB::table($table)->count();

        if ($count === 0) {
            return ['passed' => true, 'details' => 'No events to check'];
        }

        // Verify events are ordered by id (auto-increment preserves order)
        $outOfOrder = DB::table("{$table} as e1")
            ->join("{$table} as e2", function ($join) {
                $join->on('e1.aggregate_uuid', '=', 'e2.aggregate_uuid')
                    ->whereColumn('e1.id', '<', 'e2.id')
                    ->whereColumn('e1.aggregate_version', '>', 'e2.aggregate_version');
            })
            ->count();

        $passed = $outOfOrder === 0;

        return [
            'passed'  => $passed,
            'details' => $passed ? 'Event ordering preserved' : "{$outOfOrder} out-of-order events found",
        ];
    }

    /**
     * @return array{passed: bool, details: string}
     */
    private function checkAggregateConsistency(string $sourceTable, string $targetTable, string $domain): array
    {
        if (! Schema::hasTable($sourceTable) || ! Schema::hasTable($targetTable)) {
            return ['passed' => false, 'details' => 'One or both tables do not exist'];
        }

        $eventClassMap = config('event-sourcing.event_class_map', []);
        $domainAliases = [];

        foreach ($eventClassMap as $alias => $className) {
            if (preg_match('/App\\\\Domain\\\\' . preg_quote($domain, '/') . '\\\\/', $className)) {
                $domainAliases[] = $alias;
            }
        }

        $sourceAggregates = DB::table($sourceTable)
            ->whereIn('event_class', $domainAliases)
            ->whereNotNull('aggregate_uuid')
            ->distinct('aggregate_uuid')
            ->count('aggregate_uuid');

        $targetAggregates = DB::table($targetTable)
            ->whereNotNull('aggregate_uuid')
            ->distinct('aggregate_uuid')
            ->count('aggregate_uuid');

        $passed = $targetAggregates >= $sourceAggregates;

        return [
            'passed'  => $passed,
            'details' => "Source aggregates: {$sourceAggregates}, Target aggregates: {$targetAggregates}",
        ];
    }
}
