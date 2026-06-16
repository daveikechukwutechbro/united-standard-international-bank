<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Monitoring\Models\EventMigration;
use App\Domain\Monitoring\Services\EventMigrationService;
use Illuminate\Console\Command;

class EventMigrateRollbackCommand extends Command
{
    protected $signature = 'event:migrate-rollback
                            {migration : The migration ID to rollback}';

    protected $description = 'Rollback a failed or completed event migration';

    public function handle(EventMigrationService $migrationService): int
    {
        $migrationId = (int) $this->argument('migration');

        $migration = EventMigration::find($migrationId);

        if (! $migration) {
            $this->error("Migration #{$migrationId} not found.");

            return self::FAILURE;
        }

        $this->info("Rolling back migration #{$migrationId}:");
        $this->table(
            ['Domain', 'Target Table', 'Events Migrated', 'Status'],
            [[$migration->domain, $migration->target_table, $migration->events_migrated, $migration->status]]
        );

        if (! $this->confirm('Proceed with rollback?')) {
            return self::SUCCESS;
        }

        $success = $migrationService->rollback($migrationId);

        if ($success) {
            $this->info('Rollback completed successfully.');

            return self::SUCCESS;
        }

        $this->error('Rollback failed. Check logs for details.');

        return self::FAILURE;
    }
}
