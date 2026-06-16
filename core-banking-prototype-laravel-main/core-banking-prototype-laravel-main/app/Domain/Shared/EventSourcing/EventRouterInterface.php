<?php

declare(strict_types=1);

namespace App\Domain\Shared\EventSourcing;

/**
 * Routes events to domain-specific tables based on event namespace.
 *
 * This interface defines the contract for resolving which database table
 * should store events for a given domain or event class.
 */
interface EventRouterInterface
{
    /**
     * Resolve the event table name for a given event class.
     *
     * @param class-string $eventClass The fully-qualified event class name
     * @return string The table name where the event should be stored
     */
    public function resolveTableForEvent(string $eventClass): string;

    /**
     * Resolve the event table name for a given domain.
     *
     * @param string $domain The domain name (e.g., 'Account', 'Exchange')
     * @return string The table name for the domain's events
     */
    public function resolveTableForDomain(string $domain): string;

    /**
     * Extract the domain name from an event class namespace.
     *
     * @param class-string $eventClass The fully-qualified event class name
     * @return string The domain name extracted from the namespace
     */
    public function extractDomain(string $eventClass): string;

    /**
     * Get the complete domain-to-table mapping.
     *
     * @return array<string, string> Domain name => table name
     */
    public function getDomainTableMap(): array;

    /**
     * Get the default fallback table name.
     */
    public function getDefaultTable(): string;
}
