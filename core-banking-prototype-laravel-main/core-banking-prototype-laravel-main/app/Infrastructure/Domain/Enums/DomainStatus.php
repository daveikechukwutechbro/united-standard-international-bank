<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain\Enums;

/**
 * Installation status for domains.
 */
enum DomainStatus: string
{
    case INSTALLED = 'installed';
    case AVAILABLE = 'available';
    case DISABLED = 'disabled';
    case MISSING_DEPS = 'missing_dependencies';

    /**
     * Get all status values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if this status indicates the domain is active.
     */
    public function isActive(): bool
    {
        return $this === self::INSTALLED;
    }
}
