<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Compliance\Services\Certification\BreachNotificationService;
use Illuminate\Console\Command;

class GdprBreachCheckCommand extends Command
{
    protected $signature = 'gdpr:breach-check
        {--format=text : Output format (text, json)}';

    protected $description = 'Check for approaching or overdue GDPR breach notification deadlines';

    public function handle(BreachNotificationService $breachService): int
    {
        $format = $this->option('format');

        $this->info('GDPR Breach Deadline Check');
        $this->info('==========================');
        $this->newLine();

        $deadlines = $breachService->checkDeadlines();

        if ($format === 'json') {
            $this->line((string) json_encode($deadlines, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        // Overdue breaches
        if ($deadlines['overdue_count'] > 0) {
            $this->error("OVERDUE: {$deadlines['overdue_count']} breach(es) past notification deadline!");
            foreach ($deadlines['overdue_breaches'] as $breach) {
                $this->line("  [{$breach['severity']}] {$breach['title']} — {$breach['hours_overdue']}h overdue");
            }
            $this->newLine();
        } else {
            $this->info('No overdue breach notifications.');
            $this->newLine();
        }

        // Approaching deadlines
        if ($deadlines['approaching_count'] > 0) {
            $this->warn("APPROACHING: {$deadlines['approaching_count']} breach(es) nearing deadline.");
            foreach ($deadlines['approaching'] as $breach) {
                $hours = round($breach['hours_remaining'], 1);
                $this->line("  [{$breach['severity']}] {$breach['title']} — {$hours}h remaining");
            }
            $this->newLine();
        } else {
            $this->info('No breaches approaching deadline.');
        }

        // Summary
        $summary = $breachService->getSummary();
        $this->newLine();
        $this->info("Total breaches: {$summary['total']} | Open: {$summary['open']}");

        return $deadlines['overdue_count'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
