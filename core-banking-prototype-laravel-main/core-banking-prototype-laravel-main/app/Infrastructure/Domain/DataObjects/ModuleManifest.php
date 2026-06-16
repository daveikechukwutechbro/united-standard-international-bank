<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain\DataObjects;

use App\Infrastructure\Domain\Enums\DomainType;
use InvalidArgumentException;
use JsonException;

/**
 * Value object representing a domain's module.json manifest.
 *
 * @immutable
 */
final readonly class ModuleManifest
{
    /**
     * @param string $name Package name (e.g., "finaegis/exchange")
     * @param string $version Semantic version
     * @param string $description Human-readable description
     * @param DomainType $type Whether core or optional
     * @param array<string, string> $requiredDependencies Required domain dependencies with version constraints
     * @param array<string, string> $optionalDependencies Optional domain dependencies
     * @param array<string> $providesInterfaces Interfaces this domain implements
     * @param array<string> $providesEvents Domain events this module emits
     * @param array<string> $providesCommands Commands this domain handles
     * @param array<string, string> $paths Path configuration for routes, migrations, etc.
     * @param string $basePath The base path where the domain is located
     */
    public function __construct(
        public string $name,
        public string $version,
        public string $description,
        public DomainType $type,
        public array $requiredDependencies,
        public array $optionalDependencies,
        public array $providesInterfaces,
        public array $providesEvents,
        public array $providesCommands,
        public array $paths,
        public string $basePath,
    ) {
    }

    /**
     * Parse a module.json file into a ModuleManifest.
     *
     * @throws InvalidArgumentException If the manifest is invalid
     */
    public static function fromFile(string $filePath): self
    {
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException("Manifest file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new InvalidArgumentException("Failed to read manifest: {$filePath}");
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException("Invalid JSON in manifest: {$e->getMessage()}");
        }

        return self::fromArray($data, dirname($filePath));
    }

    /**
     * Create from array data.
     *
     * @param array<string, mixed> $data
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $data, string $basePath): self
    {
        self::validateRequired($data, ['name', 'version', 'description', 'type']);

        $type = DomainType::tryFrom($data['type'] ?? 'optional');
        if ($type === null) {
            throw new InvalidArgumentException(
                "Invalid domain type: {$data['type']}. Must be one of: " . implode(', ', DomainType::values())
            );
        }

        $dependencies = $data['dependencies'] ?? [];

        return new self(
            name: $data['name'],
            version: $data['version'],
            description: $data['description'],
            type: $type,
            requiredDependencies: $dependencies['required'] ?? [],
            optionalDependencies: $dependencies['optional'] ?? [],
            providesInterfaces: $data['provides']['interfaces'] ?? [],
            providesEvents: $data['provides']['events'] ?? [],
            providesCommands: $data['provides']['commands'] ?? [],
            paths: $data['paths'] ?? [],
            basePath: $basePath,
        );
    }

    /**
     * Get the domain identifier (short name without vendor prefix).
     */
    public function getDomainId(): string
    {
        $parts = explode('/', $this->name);

        return $parts[1] ?? $parts[0];
    }

    /**
     * Get all dependencies (required + optional).
     *
     * @return array<string, string>
     */
    public function getAllDependencies(): array
    {
        return array_merge($this->requiredDependencies, $this->optionalDependencies);
    }

    /**
     * Check if this domain depends on another.
     */
    public function dependsOn(string $domainName): bool
    {
        $normalizedName = $this->normalizeDomainName($domainName);

        return isset($this->requiredDependencies[$normalizedName])
            || isset($this->optionalDependencies[$normalizedName]);
    }

    /**
     * Check if this domain requires another (mandatory dependency).
     */
    public function requires(string $domainName): bool
    {
        $normalizedName = $this->normalizeDomainName($domainName);

        return isset($this->requiredDependencies[$normalizedName]);
    }

    /**
     * Check if this is a core domain.
     */
    public function isCore(): bool
    {
        return $this->type === DomainType::CORE;
    }

    /**
     * Get the path for a specific component.
     */
    public function getPath(string $component): ?string
    {
        if (! isset($this->paths[$component])) {
            return null;
        }

        return $this->basePath . '/' . $this->paths[$component];
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name'         => $this->name,
            'version'      => $this->version,
            'description'  => $this->description,
            'type'         => $this->type->value,
            'dependencies' => [
                'required' => $this->requiredDependencies,
                'optional' => $this->optionalDependencies,
            ],
            'provides' => [
                'interfaces' => $this->providesInterfaces,
                'events'     => $this->providesEvents,
                'commands'   => $this->providesCommands,
            ],
            'paths' => $this->paths,
        ];
    }

    /**
     * Normalize a domain name to the full package format.
     */
    private function normalizeDomainName(string $name): string
    {
        if (! str_contains($name, '/')) {
            return "finaegis/{$name}";
        }

        return $name;
    }

    /**
     * Validate required fields exist.
     *
     * @param array<string, mixed> $data
     * @param array<string> $fields
     * @throws InvalidArgumentException
     */
    private static function validateRequired(array $data, array $fields): void
    {
        foreach ($fields as $field) {
            if (! isset($data[$field]) || $data[$field] === '') {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }
    }
}
