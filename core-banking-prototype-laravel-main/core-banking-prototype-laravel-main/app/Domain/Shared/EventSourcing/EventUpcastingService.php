<?php

declare(strict_types=1);

namespace App\Domain\Shared\EventSourcing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

/**
 * Service for upcasting stored events to their latest schema version.
 *
 * Chains upcasters to transform events through multiple versions
 * (e.g., v1 -> v2 -> v3) during replay or on-demand migration.
 */
class EventUpcastingService
{
    public function __construct(
        private readonly EventVersionRegistry $registry,
    ) {
    }

    /**
     * Upcast a single event's payload to the latest version.
     *
     * @param array<string, mixed> $payload
     * @return array{payload: array<string, mixed>, version: int, upcasted: bool}
     */
    public function upcast(string $eventClass, array $payload, int $currentVersion = 1): array
    {
        if (! $this->registry->hasUpcasters($eventClass)) {
            return ['payload' => $payload, 'version' => $currentVersion, 'upcasted' => false];
        }

        $chain = $this->registry->getUpcastChain($eventClass, $currentVersion);

        if (empty($chain)) {
            return ['payload' => $payload, 'version' => $currentVersion, 'upcasted' => false];
        }

        $result = $payload;
        $version = $currentVersion;

        foreach ($chain as $upcaster) {
            $result = $upcaster->upcast($result);
            $version = $upcaster->toVersion();
        }

        return ['payload' => $result, 'version' => $version, 'upcasted' => true];
    }

    /**
     * Upcast all events for a given event class in a table.
     *
     * @return array{total: int, upcasted: int, errors: int}
     */
    public function upcastTable(
        string $table,
        string $eventClass,
        int $batchSize = 1000,
        bool $persist = false,
    ): array {
        $total = 0;
        $upcasted = 0;
        $errors = 0;

        $targetVersion = $this->registry->getCurrentVersion($eventClass);
        $lastId = 0;

        do {
            $events = DB::table($table)
                ->where('event_class', $eventClass)
                ->where('id', '>', $lastId)
                ->where(function ($query) use ($targetVersion) {
                    $query->whereNull('event_version')
                        ->orWhere('event_version', '<', $targetVersion);
                })
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            foreach ($events as $event) {
                $total++;
                $currentVersion = (int) ($event->event_version ?? 1);

                try {
                    try {
                        $payload = json_decode($event->event_properties, true, 512, JSON_THROW_ON_ERROR);
                    } catch (JsonException $e) {
                        $errors++;
                        Log::warning('Event JSON decode failed', [
                            'event_id'    => $event->id,
                            'event_class' => $eventClass,
                            'error'       => $e->getMessage(),
                        ]);
                        continue;
                    }

                    $result = $this->upcast($eventClass, $payload, $currentVersion);

                    if ($result['upcasted'] && $persist) {
                        DB::table($table)
                            ->where('id', $event->id)
                            ->update([
                                'event_properties' => json_encode($result['payload']),
                                'event_version'    => $result['version'],
                            ]);
                    }

                    if ($result['upcasted']) {
                        $upcasted++;
                    }
                } catch (Throwable $e) {
                    $errors++;
                    Log::warning('Event upcasting failed', [
                        'event_id'    => $event->id,
                        'event_class' => $eventClass,
                        'error'       => $e->getMessage(),
                    ]);
                }
            }

            if ($events->isNotEmpty()) {
                $lastId = $events->last()->id;
            }
        } while ($events->count() === $batchSize);

        return ['total' => $total, 'upcasted' => $upcasted, 'errors' => $errors];
    }

    /**
     * Get the version registry.
     */
    public function getRegistry(): EventVersionRegistry
    {
        return $this->registry;
    }
}
