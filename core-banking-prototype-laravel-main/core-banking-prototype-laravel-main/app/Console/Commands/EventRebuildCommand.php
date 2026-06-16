<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class EventRebuildCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:rebuild
                            {aggregate : The aggregate class name (e.g., TransactionAggregate)}
                            {--uuid= : Specific aggregate UUID to rebuild}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild aggregate state by replaying its events';

    /**
     * Known aggregate class mappings.
     *
     * @var array<string, string>
     */
    private array $aggregateMap = [
        'TransactionAggregate'           => 'App\\Domain\\Account\\Aggregates\\TransactionAggregate',
        'TransferAggregate'              => 'App\\Domain\\Account\\Aggregates\\TransferAggregate',
        'LedgerAggregate'                => 'App\\Domain\\Account\\Aggregates\\LedgerAggregate',
        'AssetTransactionAggregate'      => 'App\\Domain\\Account\\Aggregates\\AssetTransactionAggregate',
        'AssetTransferAggregate'         => 'App\\Domain\\Account\\Aggregates\\AssetTransferAggregate',
        'StablecoinAggregate'            => 'App\\Domain\\Stablecoin\\Aggregates\\StablecoinAggregate',
        'CollateralPositionAggregate'    => 'App\\Domain\\Stablecoin\\Aggregates\\CollateralPositionAggregate',
        'TreasuryAggregate'              => 'App\\Domain\\Treasury\\Aggregates\\TreasuryAggregate',
        'PortfolioAggregate'             => 'App\\Domain\\Treasury\\Aggregates\\PortfolioAggregate',
        'MetricsAggregate'               => 'App\\Domain\\Monitoring\\Aggregates\\MetricsAggregate',
        'TraceAggregate'                 => 'App\\Domain\\Monitoring\\Aggregates\\TraceAggregate',
        'ComplianceAggregate'            => 'App\\Domain\\Compliance\\Aggregates\\ComplianceAggregate',
        'ComplianceAlertAggregate'       => 'App\\Domain\\Compliance\\Aggregates\\ComplianceAlertAggregate',
        'AmlScreeningAggregate'          => 'App\\Domain\\Compliance\\Aggregates\\AmlScreeningAggregate',
        'TransactionMonitoringAggregate' => 'App\\Domain\\Compliance\\Aggregates\\TransactionMonitoringAggregate',
        'BlockchainWallet'               => 'App\\Domain\\Wallet\\Aggregates\\BlockchainWallet',
        'BatchAggregate'                 => 'App\\Domain\\Batch\\Aggregates\\BatchAggregate',
        'RefundAggregate'                => 'App\\Domain\\Cgo\\Aggregates\\RefundAggregate',
        'AIInteractionAggregate'         => 'App\\Domain\\AI\\Aggregates\\AIInteractionAggregate',
        'PaymentDepositAggregate'        => 'App\\Domain\\Payment\\Aggregates\\PaymentDepositAggregate',
        'PaymentWithdrawalAggregate'     => 'App\\Domain\\Payment\\Aggregates\\PaymentWithdrawalAggregate',
        'AgentWalletAggregate'           => 'App\\Domain\\AgentProtocol\\Aggregates\\AgentWalletAggregate',
        'EscrowAggregate'                => 'App\\Domain\\AgentProtocol\\Aggregates\\EscrowAggregate',
        'ReputationAggregate'            => 'App\\Domain\\AgentProtocol\\Aggregates\\ReputationAggregate',
        'UserProfile'                    => 'App\\Domain\\User\\Aggregates\\UserProfile',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $aggregateName = $this->argument('aggregate');
        $uuid = $this->option('uuid');
        $force = (bool) $this->option('force');

        // Resolve aggregate class
        $aggregateClass = $this->resolveAggregateClass($aggregateName);

        if ($aggregateClass === null) {
            $this->error("Unknown aggregate: {$aggregateName}");
            $this->info('Available aggregates:');
            foreach (array_keys($this->aggregateMap) as $name) {
                $this->line("  - {$name}");
            }

            return Command::FAILURE;
        }

        if (! class_exists($aggregateClass)) {
            $this->error("Aggregate class not found: {$aggregateClass}");

            return Command::FAILURE;
        }

        $this->info("Aggregate: {$aggregateClass}");

        if ($uuid) {
            return $this->rebuildSingle($aggregateClass, $uuid, $force);
        }

        return $this->rebuildAll($aggregateClass, $force);
    }

    private function resolveAggregateClass(string $name): ?string
    {
        // Check direct mapping
        if (isset($this->aggregateMap[$name])) {
            return $this->aggregateMap[$name];
        }

        // Check if it's already a fully qualified class name
        if (class_exists($name) && is_subclass_of($name, AggregateRoot::class)) {
            return $name;
        }

        return null;
    }

    private function rebuildSingle(string $aggregateClass, string $uuid, bool $force): int
    {
        $this->info("Rebuilding aggregate UUID: {$uuid}");

        if (! $force && ! $this->confirm('This will rebuild the aggregate state. Continue?')) {
            $this->info('Rebuild cancelled.');

            return Command::SUCCESS;
        }

        DB::transaction(function () use ($aggregateClass, $uuid) {
            /** @var AggregateRoot $aggregate */
            $aggregate = $aggregateClass::retrieve($uuid);
            $aggregate->snapshot();
        });

        $this->info('Aggregate rebuilt and snapshot created successfully.');

        return Command::SUCCESS;
    }

    private function rebuildAll(string $aggregateClass, bool $force): int
    {
        // Find all UUIDs for this aggregate type
        $eventTable = 'stored_events';

        $uuids = DB::table($eventTable)
            ->whereNotNull('aggregate_uuid')
            ->where('aggregate_uuid', '!=', '')
            ->distinct()
            ->pluck('aggregate_uuid');

        $this->info("Found {$uuids->count()} aggregate UUIDs to rebuild.");

        if ($uuids->isEmpty()) {
            $this->info('No aggregates to rebuild.');

            return Command::SUCCESS;
        }

        if (! $force && ! $this->confirm("Rebuild {$uuids->count()} aggregates?")) {
            $this->info('Rebuild cancelled.');

            return Command::SUCCESS;
        }

        if (! app()->runningUnitTests() && $uuids->count() > 0) {
            $bar = $this->output->createProgressBar($uuids->count());
            $bar->start();

            foreach ($uuids as $uuid) {
                DB::transaction(function () use ($aggregateClass, $uuid) {
                    /** @var AggregateRoot $aggregate */
                    $aggregate = $aggregateClass::retrieve($uuid);
                    $aggregate->snapshot();
                });
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        } else {
            foreach ($uuids as $uuid) {
                DB::transaction(function () use ($aggregateClass, $uuid) {
                    /** @var AggregateRoot $aggregate */
                    $aggregate = $aggregateClass::retrieve($uuid);
                    $aggregate->snapshot();
                });
            }
        }

        $this->info("Rebuilt {$uuids->count()} aggregates successfully.");

        return Command::SUCCESS;
    }
}
