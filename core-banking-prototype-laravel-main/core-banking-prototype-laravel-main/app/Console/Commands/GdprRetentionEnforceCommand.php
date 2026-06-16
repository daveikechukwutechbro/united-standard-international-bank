<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Compliance\Services\Certification\DataRetentionService;
use Illuminate\Console\Command;

class GdprRetentionEnforceCommand extends Command
{
    protected $signature = 'gdpr:retention-enforce
        {--dry-run : Preview without deleting/archiving}
        {--seed : Seed default policies from config}
        {--format=text : Output format (text, json)}';

    protected $description = 'Enforce GDPR data retention policies (delete/archive/anonymize expired data)';

    public function handle(DataRetentionService $retentionService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $format = $this->option('format');

        $this->info('GDPR Data Retention Enforcement');
        $this->info('================================');
        $this->newLine();

        if ($this->option('seed')) {
            $this->info('Seeding default retention policies...');
            $seedResult = $retentionService->seedDefaultPolicies();
            $this->info("Created: {$seedResult['created']}, Existing: {$seedResult['existing']}");
            $this->newLine();
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE — no data will be modified.');
            $this->newLine();
        }

        $results = $retentionService->enforceRetentionPolicies($dryRun);

        if ($format === 'json') {
            $this->line((string) json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        $this->info("Policies enforced: {$results['policies_run']}");
        $this->info("Total records affected: {$results['total_affected']}");
        $this->newLine();

        foreach ($results['results'] as $result) {
            $status = $result['executed'] ? 'DONE' : 'PREVIEW';
            $this->line("  [{$status}] {$result['data_type']} — {$result['action']} — {$result['affected_count']} records (cutoff: {$result['cutoff_date']})");
        }

        $this->newLine();
        $this->info('Retention enforcement complete.');

        return Command::SUCCESS;
    }
}
