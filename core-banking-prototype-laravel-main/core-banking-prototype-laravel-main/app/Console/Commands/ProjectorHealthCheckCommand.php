<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Monitoring\Services\ProjectorHealthService;
use Illuminate\Console\Command;

class ProjectorHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projector:health
                            {--domain= : Filter projectors by domain}
                            {--stale-only : Show only stale/failed projectors}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check health status of all registered projectors';

    /**
     * Execute the console command.
     */
    public function handle(ProjectorHealthService $healthService): int
    {
        $status = $healthService->getAllProjectorStatus();
        $domain = $this->option('domain');
        $staleOnly = (bool) $this->option('stale-only');

        $projectors = collect($status['projectors']);

        if ($domain) {
            $projectors = $projectors->filter(
                fn (array $p) => strtolower($p['domain']) === strtolower($domain)
            );
        }

        if ($staleOnly) {
            $projectors = $projectors->filter(
                fn (array $p) => in_array($p['status'], ['stale', 'failed'], true)
            );
        }

        $this->info("Projector Health Check — {$status['checked_at']}");
        $this->newLine();

        $this->table(
            ['Projector', 'Domain', 'Status', 'Lag', 'Last Processed'],
            $projectors->map(fn (array $p) => [
                $p['name'],
                $p['domain'],
                $this->formatStatus($p['status']),
                $p['lag'],
                $p['last_processed_at'] ?? 'Never',
            ])->toArray()
        );

        $this->newLine();
        $this->info("Total: {$status['total_projectors']} | Healthy: {$status['healthy']} | Stale: {$status['stale']} | Failed: {$status['failed']}");

        if ($status['failed'] > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'healthy' => '<fg=green>healthy</>',
            'stale'   => '<fg=yellow>stale</>',
            'failed'  => '<fg=red>failed</>',
            default   => $status,
        };
    }
}
