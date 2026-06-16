<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain\Enums;

/**
 * Domain types for the modular architecture.
 *
 * Core domains are always required, optional domains can be installed as needed.
 */
enum DomainType: string
{
    case CORE = 'core';
    case OPTIONAL = 'optional';

    /**
     * Get all domain types.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if this type requires the domain to always be installed.
     */
    public function isRequired(): bool
    {
        return $this === self::CORE;
    }
}
