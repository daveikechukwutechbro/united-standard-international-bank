<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain\DataObjects;

/**
 * Represents a node in a dependency tree.
 *
 * @immutable
 */
final readonly class DependencyNode
{
    /**
     * @param string $name Domain name
     * @param string $version Version constraint
     * @param bool $required Whether this is a required dependency
     * @param bool $satisfied Whether the dependency is satisfied
     * @param array<DependencyNode> $children Child dependencies
     */
    public function __construct(
        public string $name,
        public string $version,
        public bool $required,
        public bool $satisfied,
        public array $children = [],
    ) {
    }

    /**
     * Check if this node and all children are satisfied.
     */
    public function isFullySatisfied(): bool
    {
        if (! $this->satisfied) {
            return false;
        }

        foreach ($this->children as $child) {
            if (! $child->isFullySatisfied()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all unsatisfied dependencies (this node and children).
     *
     * @return array<DependencyNode>
     */
    public function getUnsatisfied(): array
    {
        $unsatisfied = [];

        if (! $this->satisfied) {
            $unsatisfied[] = $this;
        }

        foreach ($this->children as $child) {
            $unsatisfied = array_merge($unsatisfied, $child->getUnsatisfied());
        }

        return $unsatisfied;
    }

    /**
     * Flatten the tree to an array of domain names.
     *
     * @return array<string>
     */
    public function flatten(): array
    {
        $names = [$this->name];

        foreach ($this->children as $child) {
            $names = array_merge($names, $child->flatten());
        }

        return array_unique($names);
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name'      => $this->name,
            'version'   => $this->version,
            'required'  => $this->required,
            'satisfied' => $this->satisfied,
            'children'  => array_map(fn (DependencyNode $child) => $child->toArray(), $this->children),
        ];
    }
}
