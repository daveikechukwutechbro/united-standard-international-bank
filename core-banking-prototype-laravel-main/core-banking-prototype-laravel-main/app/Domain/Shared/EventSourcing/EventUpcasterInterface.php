<?php

declare(strict_types=1);

namespace App\Domain\Shared\EventSourcing;

/**
 * Interface for event upcasters that transform events from one version to another.
 *
 * Upcasters allow event schema evolution by transforming stored event payloads
 * from older versions to newer ones during replay or migration.
 */
interface EventUpcasterInterface
{
    /**
     * The event class this upcaster handles.
     *
     * @return class-string
     */
    public function eventClass(): string;

    /**
     * The source version this upcaster transforms from.
     */
    public function fromVersion(): int;

    /**
     * The target version this upcaster transforms to.
     */
    public function toVersion(): int;

    /**
     * Transform the event payload from the source version to the target version.
     *
     * @param array<string, mixed> $payload The event payload to transform
     * @return array<string, mixed> The transformed payload
     */
    public function upcast(array $payload): array;

    /**
     * Whether this upcaster can handle the given event.
     */
    public function supports(string $eventClass, int $version): bool;
}
