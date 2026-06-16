<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Compliance\Services\Certification\EvidenceCollectionService;
use Illuminate\Console\Command;
use Throwable;

class ComplianceEvidenceCollectCommand extends Command
{
    protected $signature = 'compliance:collect-evidence
        {--period=quarterly : Evidence collection period (quarterly, monthly, annual)}
        {--type=all : Evidence type to collect (all, access_logs, config_snapshot, change_log)}
        {--format=json : Output format (json, text)}
        {--dry-run : Preview without saving}';

    protected $description = 'Collect SOC 2 compliance evidence for the specified period';

    public function handle(EvidenceCollectionService $service): int
    {
        $period = (string) $this->option('period');
        $type = (string) $this->option('type');
        $format = (string) $this->option('format');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("Collecting {$type} evidence for period: {$period}...");

        if ($dryRun) {
            $this->warn('DRY RUN — no records will be saved.');
        }

        try {
            $evidence = $service->collectEvidence($period, $type);

            if ($format === 'json') {
                $this->line(json_encode($evidence, JSON_PRETTY_PRINT));
            } else {
                $this->info('Evidence collected successfully.');
                $this->table(
                    ['Type', 'Records', 'Integrity Hash'],
                    collect($evidence)->map(fn ($item) => [
                        $item['evidence_type'] ?? 'unknown',
                        is_array($item['data'] ?? null) ? count($item['data']) : 1,
                        substr($item['integrity_hash'] ?? '', 0, 16) . '...',
                    ])->toArray()
                );
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Evidence collection failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
