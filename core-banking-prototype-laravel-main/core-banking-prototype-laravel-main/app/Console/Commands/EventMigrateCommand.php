<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Monitoring\Services\EventMigrationService;
use Illuminate\Console\Command;

class EventMigrateCommand extends Command
{
    protected $signature = 'event:migrate
                            {--domain= : Migrate events for a specific domain only}
                            {--batch=1000 : Batch size for migration}
                            {--dry-run : Show migration plan without executing}
                            {--verify : Verify migration integrity after completion}';

    protected $description = 'Migrate events from shared stored_events table to domain-specific tables';

    public function handle(EventMigrationService $migrationService): int
    {
        $domain = $this->option('domain');
        $batchSize = (int) $this->option('batch');
        $dryRun = (bool) $this->option('dry-run');
        $verify = (bool) $this->option('verify');

        if ($batchSize < 1) {
            $this->error('Batch size must be at least 1.');

            return self::FAILURE;
        }

        $plan = $migrationService->getMigrationPlan(
            is_string($domain) ? $domain : null
        );

        if (empty($plan)) {
            $this->info('No events to migrate.');

            return self::SUCCESS;
        }

        $this->info($dryRun ? 'Migration Plan (dry run):' : 'Migration Plan:');
        $this->newLine();

        $rows = [];
        foreach ($plan as $domainName => $details) {
            $rows[] = [$domainName, $details['source'], $details['target'], $details['count']];
        }

        $this->table(['Domain', 'Source', 'Target', 'Events'], $rows);
        $this->newLine();

        $totalEvents = array_sum(array_column($plan, 'count'));
        $this->info("Total events to migrate: {$totalEvents}");

        if ($dryRun) {
            $this->warn('Dry run mode — no changes made.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Proceed with migration?')) {
            $this->info('Migration cancelled.');

            return self::SUCCESS;
        }

        foreach ($plan as $domainName => $details) {
            $this->info("Migrating {$domainName} ({$details['count']} events)...");

            $migration = $migrationService->migrate($domainName, $batchSize);

            if ($migration->isCompleted()) {
                $this->info("  Migrated {$migration->events_migrated} events successfully.");
            } else {
                $this->error("  Migration failed: {$migration->error_message}");
            }

            if ($verify && $migration->isCompleted()) {
                $result = $migrationService->verify($domainName);
                $migration->update(['verification_result' => $result]);

                if ($result['valid']) {
                    $this->info('  Verification passed.');
                } else {
                    $this->warn('  Verification failed:');
                    foreach ($result['checks'] as $check => $info) {
                        $status = $info['passed'] ? 'PASS' : 'FAIL';
                        $this->line("    [{$status}] {$check}: {$info['details']}");
                    }
                }
            }
        }

        $this->newLine();
        $this->info('Migration complete.');

        return self::SUCCESS;
    }
}
