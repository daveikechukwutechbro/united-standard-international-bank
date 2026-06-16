<?php

declare(strict_types=1);

namespace App\Domain\Shared\EventSourcing;

use Illuminate\Support\Facades\Log;

/**
 * Tracks event versions and their registered upcasters.
 *
 * Maintains a registry of all known event versions and the upcasters
 * needed to transform events between versions.
 */
class EventVersionRegistry
{
    /** @var array<string, EventUpcasterInterface[]> Indexed by event class */
    private array $upcasters = [];

    /** @var array<string, int> Current versions by event class */
    private array $currentVersions = [];

    /**
     * Register an upcaster for an event class.
     */
    public function register(EventUpcasterInterface $upcaster): void
    {
        $key = $upcaster->eventClass();
        $this->upcasters[$key][] = $upcaster;

        // Track the highest version we can upcast to
        $current = $this->currentVersions[$key] ?? 1;
        $this->currentVersions[$key] = max($current, $upcaster->toVersion());

        // Warn if there are chain gaps after registering
        $gaps = $this->validateChain($key);
        if (! empty($gaps) && app()->bound('log')) {
            Log::warning("Event upcaster chain has gaps for {$key}", ['gaps' => $gaps]);
        }
    }

    /**
     * Get upcasters for an event class, ordered by version.
     *
     * @return EventUpcasterInterface[]
     */
    public function getUpcasters(string $eventClass): array
    {
        $upcasters = $this->upcasters[$eventClass] ?? [];

        usort($upcasters, fn (EventUpcasterInterface $a, EventUpcasterInterface $b) => $a->fromVersion() <=> $b->fromVersion());

        return $upcasters;
    }

    /**
     * Get the current (latest) version for an event class.
     */
    public function getCurrentVersion(string $eventClass): int
    {
        return $this->currentVersions[$eventClass] ?? 1;
    }

    /**
     * Check if an event class has registered upcasters.
     */
    public function hasUpcasters(string $eventClass): bool
    {
        return ! empty($this->upcasters[$eventClass]);
    }

    /**
     * Get all registered event classes with their versions.
     *
     * @return array<string, array{current_version: int, upcaster_count: int}>
     */
    public function getAllVersions(): array
    {
        $versions = [];

        foreach ($this->currentVersions as $eventClass => $version) {
            $versions[$eventClass] = [
                'current_version' => $version,
                'upcaster_count'  => count($this->upcasters[$eventClass] ?? []),
            ];
        }

        return $versions;
    }

    /**
     * Get a chain of upcasters to transform an event from one version to the latest.
     *
     * @return EventUpcasterInterface[]
     */
    public function getUpcastChain(string $eventClass, int $fromVersion): array
    {
        $chain = [];
        $currentVersion = $fromVersion;
        $upcasters = $this->getUpcasters($eventClass);

        foreach ($upcasters as $upcaster) {
            if ($upcaster->fromVersion() === $currentVersion) {
                $chain[] = $upcaster;
                $currentVersion = $upcaster->toVersion();
            }
        }

        return $chain;
    }

    /**
     * Validate the upcaster chain for an event class, detecting version gaps.
     *
     * @return string[] List of gap descriptions (empty if chain is complete)
     */
    public function validateChain(string $eventClass): array
    {
        $upcasters = $this->getUpcasters($eventClass);

        if (empty($upcasters)) {
            return [];
        }

        $gaps = [];
        $expectedFrom = $upcasters[0]->fromVersion();

        foreach ($upcasters as $i => $upcaster) {
            if ($upcaster->fromVersion() !== $expectedFrom) {
                $gaps[] = "Missing upcaster from v{$expectedFrom} to v{$upcaster->fromVersion()}";
            }
            $expectedFrom = $upcaster->toVersion();
        }

        return $gaps;
    }
}
