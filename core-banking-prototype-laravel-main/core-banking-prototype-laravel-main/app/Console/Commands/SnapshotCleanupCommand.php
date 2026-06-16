<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Monitoring\Services\EventStoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SnapshotCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'snapshot:cleanup
                            {--days=30 : Delete snapshots older than this many days (keeps latest per aggregate)}
                            {--domain= : Clean up snapshots for a specific domain}
                            {--dry-run : Show what would be deleted without executing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old snapshots while retaining the latest per aggregate';

    /**
     * Execute the console command.
     */
    public function handle(EventStoreService $eventStoreService): int
    {
        $days = (int) $this->option('days');
        $domain = $this->option('domain');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN - No snapshots will be deleted.');
        }

        $this->info("Cleaning up snapshots older than {$days} days...");

        $domainMap = $eventStoreService->getDomainTableMap();
        $totalDeleted = 0;

        // Collect snapshot tables to process
        $tablesToProcess = [];

        if ($domain) {
            $snapshotTable = $eventStoreService->resolveSnapshotTable($domain);
            if ($snapshotTable === null) {
                $this->error("No snapshot table found for domain: {$domain}");

                return Command::FAILURE;
            }
            $tablesToProcess[$domain] = $snapshotTable;
        } else {
            foreach ($domainMap as $domainName => $tables) {
                if ($tables['snapshot_table'] !== null) {
                    $tablesToProcess[$domainName] = $tables['snapshot_table'];
                }
            }
        }

        // De-duplicate tables (multiple domains may share the same snapshot table)
        $uniqueTables = array_unique($tablesToProcess);

        foreach ($uniqueTables as $domainName => $snapshotTable) {
            $this->info("Processing: {$snapshotTable} ({$domainName})");

            $stats = $eventStoreService->getSnapshotStats($snapshotTable);
            if (! ($stats['exists'] ?? false)) {
                $this->warn("  Table does not exist: {$snapshotTable}");

                continue;
            }

            $this->line('  Total snapshots: ' . ($stats['total_snapshots'] ?? 0));
            $this->line('  Unique aggregates: ' . ($stats['unique_aggregates'] ?? 0));

            if ($dryRun) {
                // Count what would be deleted
                $cutoffDate = now()->subDays($days)->toDateTimeString();
                $wouldDelete = DB::table($snapshotTable)
                    ->where('created_at', '<', $cutoffDate)
                    ->count();
                $this->line("  Would clean up to {$wouldDelete} old snapshots.");
                $totalDeleted += $wouldDelete;
            } else {
                $deleted = $eventStoreService->cleanupSnapshots($snapshotTable, $days);
                $this->line("  Deleted {$deleted} old snapshots.");
                $totalDeleted += $deleted;
            }
        }

        if ($dryRun) {
            $this->info("Dry run complete. Up to {$totalDeleted} snapshots would be cleaned up.");
        } else {
            $this->info("Snapshot cleanup complete. Deleted {$totalDeleted} old snapshots.");
        }

        return Command::SUCCESS;
    }
}
