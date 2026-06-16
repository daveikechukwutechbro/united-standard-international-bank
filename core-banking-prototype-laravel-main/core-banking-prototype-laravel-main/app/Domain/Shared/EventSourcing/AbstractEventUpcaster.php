<?php

declare(strict_types=1);

namespace App\Domain\Shared\EventSourcing;

/**
 * Base class for event upcasters.
 *
 * Provides common functionality for transforming events between versions.
 * Subclasses only need to implement the `upcast()` method.
 */
abstract class AbstractEventUpcaster implements EventUpcasterInterface
{
    public function __construct(
        private readonly string $eventClass,
        private readonly int $fromVersion,
        private readonly int $toVersion,
    ) {
    }

    public function eventClass(): string
    {
        return $this->eventClass;
    }

    public function fromVersion(): int
    {
        return $this->fromVersion;
    }

    public function toVersion(): int
    {
        return $this->toVersion;
    }

    public function supports(string $eventClass, int $version): bool
    {
        return $eventClass === $this->eventClass && $version === $this->fromVersion;
    }
}
