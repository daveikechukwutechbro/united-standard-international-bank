<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Monitoring\Services\EventStoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Spatie\EventSourcing\Facades\Projectionist;

class EventReplayCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:replay
                            {--domain= : Replay events for a specific domain}
                            {--projector= : Replay through a specific projector class}
                            {--event-type= : Replay only specific event classes (e.g. AccountCreated)}
                            {--aggregate-id= : Replay only events for a specific aggregate UUID}
                            {--from= : Replay events from this date (Y-m-d)}
                            {--to= : Replay events up to this date (Y-m-d)}
                            {--dry-run : Show what would be replayed without executing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replay stored events through projectors to rebuild read models';

    /**
     * Execute the console command.
     */
    public function handle(EventStoreService $eventStoreService): int
    {
        $domain = $this->option('domain');
        $projectorClass = $this->option('projector');
        $eventType = $this->option('event-type');
        $aggregateId = $this->option('aggregate-id');
        $from = $this->option('from');
        $to = $this->option('to');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN - No events will be replayed.');
        }

        // Display event-type filter if provided
        if ($eventType) {
            $this->info("Event type filter: {$eventType}");
        }

        // Display aggregate-id filter if provided
        if ($aggregateId) {
            $this->info("Aggregate ID filter: {$aggregateId}");
        }

        // Validate projector class if provided
        if ($projectorClass) {
            if (! str_starts_with($projectorClass, 'App\\')) {
                $this->error("Invalid projector class: {$projectorClass} (must be in App\\ namespace)");

                return Command::FAILURE;
            }

            if (! class_exists($projectorClass)) {
                $this->error("Projector class not found: {$projectorClass}");

                return Command::FAILURE;
            }

            if (! is_subclass_of($projectorClass, Projector::class)) {
                $this->error("Class is not a Projector: {$projectorClass}");

                return Command::FAILURE;
            }

            $this->info("Projector filter: {$projectorClass}");
        }

        // Validate domain if provided
        if ($domain) {
            $eventTable = $eventStoreService->resolveEventTable($domain);
            if ($eventTable === null) {
                $this->error("Unknown domain: {$domain}");

                return Command::FAILURE;
            }

            $eventCount = $eventStoreService->countEvents($eventTable, $from, $to);
            $this->info("Domain: {$domain}");
            $this->info("Event table: {$eventTable}");
            $this->info("Events to replay: {$eventCount}");
        } else {
            $allStats = $eventStoreService->getAllStats();
            $totalEvents = $allStats['summary']['total_events'] ?? 0;
            $this->info("Replaying all events ({$totalEvents} total)");
        }

        if ($from) {
            $this->info("From: {$from}");
        }
        if ($to) {
            $this->info("To: {$to}");
        }

        if ($dryRun) {
            $this->info('Dry run complete. No events were replayed.');

            return Command::SUCCESS;
        }

        if (! $this->confirm('This will replay events through projectors. Continue?')) {
            $this->info('Replay cancelled.');

            return Command::SUCCESS;
        }

        $this->info('Starting event replay...');

        // Determine which projectors to replay
        $projectors = $this->resolveProjectors($projectorClass, $domain);

        DB::transaction(function () use ($projectors, $eventType, $aggregateId) {
            if ($eventType || $aggregateId) {
                $this->replayWithFilters($projectors, $eventType, $aggregateId);
            } else {
                Projectionist::replay($projectors);
            }
        });

        $this->info('Event replay completed successfully.');

        return Command::SUCCESS;
    }

    /**
     * Resolve projectors based on filters.
     *
     * @return Collection<int, mixed>
     */
    private function resolveProjectors(?string $projectorClass, ?string $domain): Collection
    {
        if ($projectorClass) {
            return collect([app($projectorClass)]);
        }

        $projectors = Projectionist::getProjectors();

        // Filter by domain namespace if --domain is specified
        if ($domain) {
            $domainNamespace = "App\\Domain\\{$domain}\\";

            return $projectors->filter(
                fn (object $projector) => str_starts_with($projector::class, $domainNamespace)
            );
        }

        return $projectors;
    }

    /**
     * Replay events with event-type and/or aggregate-id filters.
     *
     * @param  Collection<int, mixed>  $projectors
     */
    private function replayWithFilters(Collection $projectors, ?string $eventType, ?string $aggregateId): void
    {
        $query = DB::table('stored_events');

        if ($eventType) {
            $query->where('event_class', 'LIKE', "%{$eventType}");
        }

        if ($aggregateId) {
            $query->where('aggregate_uuid', $aggregateId);
        }

        $eventCount = $query->count();
        $this->info("Filtered events to replay: {$eventCount}");

        if ($eventCount === 0) {
            $this->warn('No events match the provided filters.');

            return;
        }

        Projectionist::replay($projectors, onEventReplayed: function (object $event) use ($eventType, $aggregateId): bool {
            if ($eventType && ! str_ends_with($event::class, $eventType)) {
                return false;
            }

            if ($aggregateId && method_exists($event, 'aggregateRootUuid') && $event->aggregateRootUuid() !== $aggregateId) {
                return false;
            }

            return true;
        });
    }
}
