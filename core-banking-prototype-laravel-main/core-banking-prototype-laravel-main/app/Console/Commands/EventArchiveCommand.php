<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Monitoring\Services\EventArchivalService;
use App\Domain\Monitoring\Services\EventStoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class EventArchiveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:archive
        {--before= : Archive events created before this date (Y-m-d)}
        {--domain= : Archive events for a specific domain only}
        {--batch-size=1000 : Number of events to process per batch}
        {--dry-run : Show what would be archived without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive old events from the event store to the archived_events table';

    /**
     * Execute the console command.
     */
    public function handle(EventArchivalService $archivalService, EventStoreService $eventStoreService): int
    {
        if (! Schema::hasTable('archived_events')) {
            $this->error('The archived_events table does not exist. Run migrations first.');

            return self::FAILURE;
        }

        $beforeDate = $this->option('before')
            ?? now()->subDays(config('event-store.archival.default_retention_days', 365))->toDateString();
        $domain = $this->option('domain');
        $batchSize = (int) $this->option('batch-size');
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? '[DRY RUN] Simulating event archival...' : 'Starting event archival...');
        $this->line("  Before: {$beforeDate}");
        $this->line("  Batch size: {$batchSize}");

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
            $this->line('  Archiving from all event tables (' . count($tables) . ' tables)');
        }

        $totalArchived = 0;

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $count = $eventStoreService->countEvents($table, null, $beforeDate);
            $this->line("  {$table}: {$count} events eligible for archival");

            if ($dryRun) {
                $totalArchived += $count;

                continue;
            }

            $archived = $archivalService->archiveEvents($table, $beforeDate, $batchSize);
            $totalArchived += $archived;
            $this->line("    Archived: {$archived} events");
        }

        if ($dryRun) {
            $this->info("[DRY RUN] Would archive {$totalArchived} events total.");
        } else {
            $this->info("Archived {$totalArchived} events total.");
        }

        return self::SUCCESS;
    }
}
