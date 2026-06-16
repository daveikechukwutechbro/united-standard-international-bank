<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class DomainStatusCommand extends Command
{
    protected $signature = 'domain:status
        {domain? : Specific domain to check}
        {--models : Show model counts}
        {--events : Show event counts}';

    protected $description = 'Show domain health overview — models, events, and projectors';

    /**
     * @var array<string, array{path: string, namespace: string}>
     */
    private array $domains = [
        'Account'       => ['path' => 'app/Domain/Account', 'namespace' => 'App\\Domain\\Account'],
        'Exchange'      => ['path' => 'app/Domain/Exchange', 'namespace' => 'App\\Domain\\Exchange'],
        'Wallet'        => ['path' => 'app/Domain/Wallet', 'namespace' => 'App\\Domain\\Wallet'],
        'Compliance'    => ['path' => 'app/Domain/Compliance', 'namespace' => 'App\\Domain\\Compliance'],
        'Lending'       => ['path' => 'app/Domain/Lending', 'namespace' => 'App\\Domain\\Lending'],
        'Treasury'      => ['path' => 'app/Domain/Treasury', 'namespace' => 'App\\Domain\\Treasury'],
        'Payment'       => ['path' => 'app/Domain/Payment', 'namespace' => 'App\\Domain\\Payment'],
        'Fraud'         => ['path' => 'app/Domain/Fraud', 'namespace' => 'App\\Domain\\Fraud'],
        'Mobile'        => ['path' => 'app/Domain/Mobile', 'namespace' => 'App\\Domain\\Mobile'],
        'MobilePayment' => ['path' => 'app/Domain/MobilePayment', 'namespace' => 'App\\Domain\\MobilePayment'],
        'TrustCert'     => ['path' => 'app/Domain/TrustCert', 'namespace' => 'App\\Domain\\TrustCert'],
        'CrossChain'    => ['path' => 'app/Domain/CrossChain', 'namespace' => 'App\\Domain\\CrossChain'],
        'DeFi'          => ['path' => 'app/Domain/DeFi', 'namespace' => 'App\\Domain\\DeFi'],
        'Privacy'       => ['path' => 'app/Domain/Privacy', 'namespace' => 'App\\Domain\\Privacy'],
        'Stablecoin'    => ['path' => 'app/Domain/Stablecoin', 'namespace' => 'App\\Domain\\Stablecoin'],
    ];

    public function handle(): int
    {
        $specificDomain = $this->argument('domain');

        if ($specificDomain) {
            if (! isset($this->domains[$specificDomain])) {
                $this->error("Unknown domain: {$specificDomain}");
                $this->line('Available domains: ' . implode(', ', array_keys($this->domains)));

                return self::FAILURE;
            }

            $this->domains = [$specificDomain => $this->domains[$specificDomain]];
        }

        $this->info('Domain Health Overview');
        $this->newLine();

        $rows = [];

        foreach ($this->domains as $name => $config) {
            $domainPath = base_path($config['path']);

            if (! File::isDirectory($domainPath)) {
                $rows[] = [$name, 'N/A', 'N/A', 'N/A', 'N/A', 'Missing'];

                continue;
            }

            $models = $this->countFiles("{$domainPath}/Models", '*.php');
            $services = $this->countFiles("{$domainPath}/Services", '*.php');
            $events = $this->countFiles("{$domainPath}/Events", '*.php');
            $projectors = $this->countFiles("{$domainPath}/Projectors", '*.php');

            $graphql = File::exists(base_path('graphql/' . strtolower($name) . '.graphql'))
                ? 'Yes' : 'No';

            $rows[] = [$name, (string) $models, (string) $services, (string) $events, (string) $projectors, $graphql];
        }

        $this->table(
            ['Domain', 'Models', 'Services', 'Events', 'Projectors', 'GraphQL'],
            $rows
        );

        if ($this->option('models')) {
            $this->showModelCounts();
        }

        if ($this->option('events')) {
            $this->showEventCounts();
        }

        return self::SUCCESS;
    }

    private function countFiles(string $directory, string $pattern): int
    {
        if (! File::isDirectory($directory)) {
            return 0;
        }

        return count(File::glob("{$directory}/{$pattern}"));
    }

    private function showModelCounts(): void
    {
        $this->newLine();
        $this->info('Model Record Counts:');

        $tables = [
            'accounts'             => 'Accounts',
            'wallets'              => 'Wallets',
            'orders'               => 'Orders',
            'trades'               => 'Trades',
            'payment_transactions' => 'Payments',
            'loan_applications'    => 'Loan Applications',
            'compliance_alerts'    => 'Compliance Alerts',
            'fraud_cases'          => 'Fraud Cases',
            'stored_events'        => 'Stored Events',
        ];

        $rows = [];
        foreach ($tables as $table => $label) {
            try {
                $count = DB::table($table)->count();
                $rows[] = [$label, number_format($count)];
            } catch (Throwable) {
                $rows[] = [$label, 'N/A'];
            }
        }

        $this->table(['Model', 'Count'], $rows);
    }

    private function showEventCounts(): void
    {
        $this->newLine();
        $this->info('Event Counts by Type:');

        try {
            $events = DB::table('stored_events')
                ->select('event_class', DB::raw('COUNT(*) as count'))
                ->groupBy('event_class')
                ->orderByDesc('count')
                ->limit(20)
                ->get();

            $rows = [];
            foreach ($events as $event) {
                $shortName = class_basename($event->event_class);
                $rows[] = [$shortName, number_format($event->count)];
            }

            if (empty($rows)) {
                $this->line('  No events found.');
            } else {
                $this->table(['Event Type', 'Count'], $rows);
            }
        } catch (Throwable) {
            $this->warn('  Could not query events table.');
        }
    }
}
