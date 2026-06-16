<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Monitoring\Services\EventArchivalService;
use App\Domain\Monitoring\Services\EventStoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EventCompactCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:compact
        {--domain= : Compact events for a specific domain only}
        {--keep-latest=100 : Number of latest events to keep per aggregate}
        {--dry-run : Show what would be compacted without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compact event store by removing old events for aggregates with snapshots';

    /**
     * Execute the console command.
     */
    public function handle(EventArchivalService $archivalService, EventStoreService $eventStoreService): int
    {
        $domain = $this->option('domain');
        $keepLatest = (int) $this->option('keep-latest');
        $dryRun = (bool) $this->option('dry-run');
        $requireSnapshot = config('event-store.compaction.require_snapshot', true);

        $this->info($dryRun ? '[DRY RUN] Simulating event compaction...' : 'Starting event compaction...');
        $this->line("  Keep latest: {$keepLatest} events per aggregate");
        $this->line('  Require snapshot: ' . ($requireSnapshot ? 'yes' : 'no'));

        if ($domain) {
            $eventTable = $eventStoreService->resolveEventTable($domain);
            if ($eventTable === null) {
                $this->error("Domain '{$domain}' not found in event store mapping.");

                return self::FAILURE;
            }
            $tables = [$eventTable];
            $this->line("  Domain: {$domain} (table: {$eventTable})");
        } else {
            $tables = $eventStoreService->discoverEventTables();
            $this->line('  Compacting all event tables (' . count($tables) . ' tables)');
        }

        $totalCompacted = 0;

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $aggregates = DB::table($table)
                ->select('aggregate_uuid')
                ->groupBy('aggregate_uuid')
                ->havingRaw('count(*) > ?', [$keepLatest])
                ->pluck('aggregate_uuid');

            if ($aggregates->isEmpty()) {
                $this->line("  {$table}: No aggregates need compaction");

                continue;
            }

            $this->line("  {$table}: {$aggregates->count()} aggregates eligible for compaction");

            foreach ($aggregates as $uuid) {
                if ($dryRun) {
                    $eventCount = DB::table($table)
                        ->where('aggregate_uuid', $uuid)
                        ->count();
                    $wouldRemove = max(0, $eventCount - $keepLatest);
                    $totalCompacted += $wouldRemove;

                    continue;
                }

                $compacted = $archivalService->compactAggregate(
                    $table,
                    $uuid,
                    $keepLatest,
                    $requireSnapshot,
                );
                $totalCompacted += $compacted;
            }
        }

        if ($dryRun) {
            $this->info("[DRY RUN] Would compact {$totalCompacted} events total.");
        } else {
            $this->info("Compacted {$totalCompacted} events total.");
        }

        return self::SUCCESS;
    }
}
