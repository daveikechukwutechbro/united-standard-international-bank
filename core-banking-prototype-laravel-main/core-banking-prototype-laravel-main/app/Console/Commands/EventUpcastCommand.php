<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Shared\EventSourcing\EventRouterInterface;
use App\Domain\Shared\EventSourcing\EventUpcastingService;
use Illuminate\Console\Command;

class EventUpcastCommand extends Command
{
    protected $signature = 'event:upcast
                            {--domain= : Upcast events for a specific domain}
                            {--event= : Upcast a specific event class alias}
                            {--batch=1000 : Batch size}
                            {--persist : Persist upcasted events to database}';

    protected $description = 'Upcast stored events to their latest schema version';

    public function handle(
        EventUpcastingService $upcastingService,
        EventRouterInterface $eventRouter,
    ): int {
        $domain = $this->option('domain');
        $eventAlias = $this->option('event');
        $batchSize = (int) $this->option('batch');
        $persist = (bool) $this->option('persist');

        $registry = $upcastingService->getRegistry();
        $versions = $registry->getAllVersions();

        if (empty($versions)) {
            $this->info('No event upcasters registered.');

            return self::SUCCESS;
        }

        $eventClassMap = config('event-sourcing.event_class_map', []);
        $eventsToUpcast = [];

        if ($eventAlias && is_string($eventAlias)) {
            if (isset($eventClassMap[$eventAlias])) {
                $eventsToUpcast[$eventAlias] = $eventClassMap[$eventAlias];
            } else {
                $this->error("Unknown event alias: {$eventAlias}");

                return self::FAILURE;
            }
        } else {
            foreach ($eventClassMap as $alias => $className) {
                if ($domain && is_string($domain)) {
                    if (! str_contains($className, "App\\Domain\\{$domain}\\")) {
                        continue;
                    }
                }

                if ($registry->hasUpcasters($alias)) {
                    $eventsToUpcast[$alias] = $className;
                }
            }
        }

        if (empty($eventsToUpcast)) {
            $this->info('No events require upcasting.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s events to upcast (%s mode):',
            count($eventsToUpcast),
            $persist ? 'PERSIST' : 'DRY RUN',
        ));

        $this->newLine();

        foreach ($eventsToUpcast as $alias => $className) {
            $table = $eventRouter->resolveTableForEvent($className);
            $this->info("Upcasting {$alias} in {$table}...");

            $result = $upcastingService->upcastTable($table, $alias, $batchSize, $persist);

            $this->line("  Total: {$result['total']}, Upcasted: {$result['upcasted']}, Errors: {$result['errors']}");
        }

        $this->newLine();
        $this->info('Upcasting complete.');

        return self::SUCCESS;
    }
}
