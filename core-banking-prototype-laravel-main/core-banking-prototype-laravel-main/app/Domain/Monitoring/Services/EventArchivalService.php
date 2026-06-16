<?php

declare(strict_types=1);

namespace App\Domain\Monitoring\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EventArchivalService
{
    public function __construct(
        private readonly EventStoreService $eventStoreService,
    ) {
    }

    /**
     * Archive events from a source table to the archived_events table.
     *
     * @return int Number of events archived
     */
    public function archiveEvents(string $sourceTable, string $beforeDate, int $batchSize = 1000): int
    {
        if (! Schema::hasTable($sourceTable) || ! Schema::hasTable('archived_events')) {
            return 0;
        }

        $totalArchived = 0;

        do {
            $events = DB::table($sourceTable)
                ->where('created_at', '<', $beforeDate)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($events->isEmpty()) {
                break;
            }

            DB::transaction(function () use ($events, $sourceTable, &$totalArchived) {
                $inserts = $events->map(function ($event) use ($sourceTable) {
                    return [
                        'source_table'        => $sourceTable,
                        'aggregate_uuid'      => $event->aggregate_uuid ?? '',
                        'aggregate_version'   => $event->aggregate_version ?? null,
                        'event_version'       => $event->event_version ?? 1,
                        'event_class'         => $event->event_class ?? '',
                        'event_properties'    => $event->event_properties ?? '{}',
                        'meta_data'           => $event->meta_data ?? null,
                        'original_created_at' => $event->created_at,
                        'archived_at'         => now(),
                    ];
                })->toArray();

                DB::table('archived_events')->insert($inserts);

                $eventIds = $events->pluck('id')->toArray();
                DB::table($sourceTable)->whereIn('id', $eventIds)->delete();

                $totalArchived += count($eventIds);
            });
        } while ($events->count() === $batchSize);

        return $totalArchived;
    }

    /**
     * Compact events for an aggregate, keeping only the latest N events.
     * Only compacts if a snapshot exists after the events being removed.
     *
     * @return int Number of events compacted (removed)
     */
    public function compactAggregate(
        string $sourceTable,
        string $aggregateUuid,
        int $keepLatest = 100,
        bool $requireSnapshot = true,
    ): int {
        if (! Schema::hasTable($sourceTable)) {
            return 0;
        }

        $totalEvents = DB::table($sourceTable)
            ->where('aggregate_uuid', $aggregateUuid)
            ->count();

        if ($totalEvents <= $keepLatest) {
            return 0;
        }

        // If snapshot is required, verify one exists
        if ($requireSnapshot) {
            $domain = $this->eventStoreService->resolveDomainByEventTable($sourceTable);
            if ($domain === null) {
                return 0;
            }

            $snapshotTable = $this->eventStoreService->resolveSnapshotTable($domain);
            if ($snapshotTable === null || ! Schema::hasTable($snapshotTable)) {
                return 0;
            }

            $hasSnapshot = DB::table($snapshotTable)
                ->where('aggregate_uuid', $aggregateUuid)
                ->exists();

            if (! $hasSnapshot) {
                return 0;
            }
        }

        // Get the IDs of events to keep (the latest N)
        $keepIds = DB::table($sourceTable)
            ->where('aggregate_uuid', $aggregateUuid)
            ->orderByDesc('id')
            ->limit($keepLatest)
            ->pluck('id')
            ->toArray();

        $deleted = 0;

        DB::transaction(function () use ($sourceTable, $aggregateUuid, $keepIds, &$deleted) {
            $deleted = DB::table($sourceTable)
                ->where('aggregate_uuid', $aggregateUuid)
                ->whereNotIn('id', $keepIds)
                ->delete();
        });

        return $deleted;
    }

    /**
     * Get archival statistics.
     *
     * @return array<string, mixed>
     */
    public function getArchivalStats(): array
    {
        if (! Schema::hasTable('archived_events')) {
            return [
                'archived_events' => 0,
                'source_tables'   => [],
            ];
        }

        $total = DB::table('archived_events')->count();

        $bySource = DB::table('archived_events')
            ->selectRaw('source_table, count(*) as count')
            ->groupBy('source_table')
            ->pluck('count', 'source_table')
            ->toArray();

        $oldest = DB::table('archived_events')->min('original_created_at');
        $newest = DB::table('archived_events')->max('original_created_at');

        return [
            'archived_events' => $total,
            'source_tables'   => $bySource,
            'oldest_event'    => $oldest,
            'newest_event'    => $newest,
        ];
    }

    /**
     * Restore events from the archive back to the source table.
     *
     * @return int Number of events restored
     */
    public function restoreFromArchive(string $sourceTable, ?string $aggregateUuid = null): int
    {
        if (! Schema::hasTable('archived_events') || ! Schema::hasTable($sourceTable)) {
            return 0;
        }

        $batchSize = (int) config('event-store.archival.batch_size', 1000);
        $restored = 0;

        do {
            $query = DB::table('archived_events')
                ->where('source_table', $sourceTable);

            if ($aggregateUuid !== null) {
                $query->where('aggregate_uuid', $aggregateUuid);
            }

            $events = $query->orderBy('id')->limit($batchSize)->get();

            if ($events->isEmpty()) {
                break;
            }

            DB::transaction(function () use ($events, $sourceTable, &$restored) {
                foreach ($events as $event) {
                    DB::table($sourceTable)->insert([
                        'aggregate_uuid'    => $event->aggregate_uuid,
                        'aggregate_version' => $event->aggregate_version,
                        'event_version'     => $event->event_version,
                        'event_class'       => $event->event_class,
                        'event_properties'  => $event->event_properties,
                        'meta_data'         => $event->meta_data,
                        'created_at'        => $event->original_created_at,
                    ]);
                }

                $archiveIds = $events->pluck('id')->toArray();
                DB::table('archived_events')->whereIn('id', $archiveIds)->delete();

                $restored += count($archiveIds);
            });
        } while ($events->count() === $batchSize);

        return $restored;
    }
}
