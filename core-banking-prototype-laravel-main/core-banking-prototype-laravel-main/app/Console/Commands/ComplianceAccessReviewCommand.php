<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Compliance\Services\Certification\AccessReviewService;
use Illuminate\Console\Command;
use Throwable;

class ComplianceAccessReviewCommand extends Command
{
    protected $signature = 'compliance:access-review
        {--format=text : Output format (json, text)}
        {--export : Export to file}';

    protected $description = 'Run a SOC 2 access review and report on privileged users, permissions, and stale accounts';

    public function handle(AccessReviewService $service): int
    {
        $format = (string) $this->option('format');
        $export = (bool) $this->option('export');

        $this->info('Running access review...');

        try {
            $report = $service->runAccessReview();
            $summary = $report['summary'] ?? $report;

            if ($format === 'json') {
                $output = json_encode($report, JSON_PRETTY_PRINT);
                $this->line($output);

                if ($export) {
                    $path = storage_path('app/compliance/access-review-' . date('Y-m-d') . '.json');
                    if (! is_dir(dirname($path))) {
                        mkdir(dirname($path), 0755, true);
                    }
                    file_put_contents($path, $output);
                    $this->info("Exported to: {$path}");
                }
            } else {
                $this->info('Privileged Users: ' . ($summary['privileged_user_count'] ?? 0));
                $this->info('Total Roles: ' . ($summary['total_roles'] ?? 0));
                $this->info('Stale Accounts: ' . ($summary['stale_account_count'] ?? 0));
                $this->info('Dormant Tokens: ' . ($summary['dormant_token_count'] ?? 0));

                if (! empty($report['recommendations'])) {
                    $this->warn('Recommendations:');
                    foreach ($report['recommendations'] as $rec) {
                        $this->line("  - {$rec}");
                    }
                }
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Access review failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
