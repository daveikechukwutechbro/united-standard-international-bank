<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Monitoring\Services\EventStoreService;
use Illuminate\Console\Command;

class EventStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:stats
                            {--domain= : Filter by domain name}
                            {--format=table : Output format (table or json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display event store statistics and growth metrics';

    /**
     * Execute the console command.
     */
    public function handle(EventStoreService $eventStoreService): int
    {
        $domain = $this->option('domain');
        $format = is_string($this->option('format')) ? $this->option('format') : 'table';

        $this->info('Gathering event store statistics...');

        if ($domain) {
            return $this->showDomainStats($eventStoreService, $domain, $format);
        }

        return $this->showAllStats($eventStoreService, $format);
    }

    private function showDomainStats(EventStoreService $service, string $domain, string $format): int
    {
        $eventTable = $service->resolveEventTable($domain);

        if ($eventTable === null) {
            $this->error("Unknown domain: {$domain}");

            return Command::FAILURE;
        }

        $tableStats = $service->getTableStats($eventTable);
        $snapshotTable = $service->resolveSnapshotTable($domain);
        $snapshotStats = $snapshotTable ? $service->getSnapshotStats($snapshotTable) : null;

        if ($format === 'json') {
            $this->line((string) json_encode([
                'domain'    => $domain,
                'events'    => $tableStats,
                'snapshots' => $snapshotStats,
            ], JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Domain', $domain],
                ['Event Table', $eventTable],
                ['Total Events', (string) $tableStats['total_events']],
                ['Unique Aggregates', (string) ($tableStats['unique_aggregates'] ?? 0)],
                ['Oldest Event', (string) ($tableStats['oldest_event'] ?? 'N/A')],
                ['Newest Event', (string) ($tableStats['newest_event'] ?? 'N/A')],
                ['Snapshot Table', $snapshotTable ?? 'N/A'],
                ['Total Snapshots', (string) ($snapshotStats['total_snapshots'] ?? 0)],
            ],
        );

        return Command::SUCCESS;
    }

    private function showAllStats(EventStoreService $service, string $format): int
    {
        $allStats = $service->getAllStats();

        if ($format === 'json') {
            $this->line((string) json_encode($allStats, JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        }

        // Summary table
        $summary = $allStats['summary'] ?? [];
        $this->info('Event Store Summary');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Events', (string) ($summary['total_events'] ?? 0)],
                ['Total Aggregates', (string) ($summary['total_aggregates'] ?? 0)],
                ['Total Snapshots', (string) ($summary['total_snapshots'] ?? 0)],
                ['Events Today', (string) ($summary['events_today'] ?? 0)],
                ['Domains', (string) ($summary['domain_count'] ?? 0)],
            ],
        );

        // Domain mapping table
        $domainMap = $service->getDomainTableMap();
        $rows = [];
        foreach ($domainMap as $domain => $tables) {
            $rows[] = [
                $domain,
                $tables['event_table'],
                $tables['snapshot_table'] ?? 'N/A',
            ];
        }

        $this->newLine();
        $this->info('Domain Table Mapping');
        $this->table(['Domain', 'Event Table', 'Snapshot Table'], $rows);

        return Command::SUCCESS;
    }
}
