<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain\DataObjects;

use App\Infrastructure\Domain\Enums\DomainStatus;
use App\Infrastructure\Domain\Enums\DomainType;

/**
 * Information about a domain for listing and management.
 *
 * @immutable
 */
final readonly class DomainInfo
{
    /**
     * @param string $name Domain identifier
     * @param string $displayName Human-readable name
     * @param string $description Domain description
     * @param DomainType $type Core or optional
     * @param DomainStatus $status Installation status
     * @param string $version Version string
     * @param array<string> $dependencies Required dependencies
     * @param array<string> $dependents Domains that depend on this one
     */
    public function __construct(
        public string $name,
        public string $displayName,
        public string $description,
        public DomainType $type,
        public DomainStatus $status,
        public string $version,
        public array $dependencies,
        public array $dependents,
    ) {
    }

    /**
     * Check if the domain can be safely removed.
     */
    public function canBeRemoved(): bool
    {
        // Core domains cannot be removed
        if ($this->type === DomainType::CORE) {
            return false;
        }

        // Cannot remove if other installed domains depend on this one
        return empty($this->dependents);
    }

    /**
     * Convert to array for display.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name'         => $this->name,
            'display_name' => $this->displayName,
            'description'  => $this->description,
            'type'         => $this->type->value,
            'status'       => $this->status->value,
            'version'      => $this->version,
            'dependencies' => $this->dependencies,
            'dependents'   => $this->dependents,
        ];
    }
}
