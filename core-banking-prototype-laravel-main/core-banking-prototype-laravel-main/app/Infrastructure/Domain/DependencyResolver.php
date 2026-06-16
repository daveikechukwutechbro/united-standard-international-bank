<?php

declare(strict_types=1);

// SPDX-License-Identifier: Apache-2.0
// Copyright (c) 2024-2026 FinAegis Contributors

namespace App\Infrastructure\Domain;

use App\Infrastructure\Domain\DataObjects\DependencyNode;
use App\Infrastructure\Domain\DataObjects\ModuleManifest;
use Composer\Semver\Semver;
use Exception;
use RuntimeException;

/**
 * Resolves dependencies between domains.
 *
 * Handles dependency tree building, circular dependency detection,
 * and installation order calculation.
 */
class DependencyResolver
{
    /**
     * @var array<string, ModuleManifest> Cache of loaded manifests
     */
    private array $manifests = [];

    /**
     * @var array<string> Domains currently in the resolution path (for cycle detection)
     */
    private array $resolutionPath = [];

    /**
     * Load manifests for resolution.
     *
     * @param array<string, ModuleManifest> $manifests
     */
    public function setManifests(array $manifests): void
    {
        $this->manifests = $manifests;
    }

    /**
     * Build the dependency tree for a domain.
     *
     * @throws RuntimeException If circular dependency detected
     */
    public function buildDependencyTree(
        string $domainName,
        bool $includeOptional = false
    ): DependencyNode {
        $this->resolutionPath = [];

        return $this->buildNodeRecursive($domainName, true, $includeOptional);
    }

    /**
     * Get installation order for a domain (dependencies first).
     *
     * @return array<string>
     * @throws RuntimeException If circular dependency detected
     */
    public function getInstallationOrder(string $domainName): array
    {
        $tree = $this->buildDependencyTree($domainName, false);
        $order = $this->flattenTreeForInstall($tree);

        // Remove the domain itself from the list (it will be installed last)
        $order = array_filter($order, fn ($name) => $name !== $this->normalizeDomainName($domainName));

        // The domain itself should be installed last
        $order[] = $this->normalizeDomainName($domainName);

        return array_values(array_unique($order));
    }

    /**
     * Get uninstallation order (dependents first).
     *
     * @param array<string> $installedDomains Currently installed domains
     * @return array<string>
     */
    public function getUninstallationOrder(string $domainName, array $installedDomains): array
    {
        $normalizedName = $this->normalizeDomainName($domainName);
        $dependents = $this->getDependentDomains($normalizedName, $installedDomains);

        // Dependent domains should be uninstalled first
        $order = array_reverse($dependents);
        $order[] = $normalizedName;

        return array_values(array_unique($order));
    }

    /**
     * Get domains that depend on the given domain.
     *
     * @param array<string> $installedDomains
     * @return array<string>
     */
    public function getDependentDomains(string $domainName, array $installedDomains): array
    {
        $normalizedName = $this->normalizeDomainName($domainName);
        $dependents = [];

        foreach ($installedDomains as $installed) {
            $manifest = $this->manifests[$installed] ?? null;
            if ($manifest === null) {
                continue;
            }

            if ($manifest->requires($normalizedName)) {
                $dependents[] = $installed;
                // Recursively find dependents
                $dependents = array_merge(
                    $dependents,
                    $this->getDependentDomains($installed, $installedDomains)
                );
            }
        }

        return array_values(array_unique($dependents));
    }

    /**
     * Check if all required dependencies are satisfied.
     *
     * @param array<string> $installedDomains
     * @return array<string> Missing dependencies
     */
    public function getMissingDependencies(string $domainName, array $installedDomains): array
    {
        $manifest = $this->manifests[$this->normalizeDomainName($domainName)] ?? null;
        if ($manifest === null) {
            return [];
        }

        $missing = [];
        foreach ($manifest->requiredDependencies as $dep => $constraint) {
            $normalizedDep = $this->normalizeDomainName($dep);
            if (! in_array($normalizedDep, $installedDomains, true)) {
                $missing[] = $normalizedDep;
            } else {
                // Check version constraint
                $depManifest = $this->manifests[$normalizedDep] ?? null;
                if ($depManifest !== null && ! $this->satisfiesVersion($depManifest->version, $constraint)) {
                    $missing[] = "{$normalizedDep} (requires {$constraint}, installed {$depManifest->version})";
                }
            }
        }

        return $missing;
    }

    /**
     * Check for circular dependencies.
     *
     * @return array<string>|null Cycle path if detected, null otherwise
     */
    public function detectCircularDependencies(string $domainName): ?array
    {
        try {
            $this->resolutionPath = [];
            $this->buildNodeRecursive($domainName, true, true);

            return null;
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Circular dependency')) {
                return $this->resolutionPath;
            }
            throw $e;
        }
    }

    /**
     * Build a dependency node recursively.
     */
    private function buildNodeRecursive(
        string $domainName,
        bool $required,
        bool $includeOptional
    ): DependencyNode {
        $normalizedName = $this->normalizeDomainName($domainName);

        // Check for circular dependencies
        if (in_array($normalizedName, $this->resolutionPath, true)) {
            $this->resolutionPath[] = $normalizedName;
            throw new RuntimeException(
                'Circular dependency detected: ' . implode(' -> ', $this->resolutionPath)
            );
        }

        $this->resolutionPath[] = $normalizedName;

        $manifest = $this->manifests[$normalizedName] ?? null;
        $satisfied = $manifest !== null;
        $version = $manifest !== null ? $manifest->version : '*';
        $children = [];

        if ($manifest !== null) {
            // Process required dependencies
            foreach ($manifest->requiredDependencies as $dep => $constraint) {
                $children[] = $this->buildNodeRecursive($dep, true, $includeOptional);
            }

            // Process optional dependencies if requested
            if ($includeOptional) {
                foreach ($manifest->optionalDependencies as $dep => $constraint) {
                    $children[] = $this->buildNodeRecursive($dep, false, $includeOptional);
                }
            }
        }

        array_pop($this->resolutionPath);

        return new DependencyNode(
            name: $normalizedName,
            version: $version,
            required: $required,
            satisfied: $satisfied,
            children: $children,
        );
    }

    /**
     * Flatten dependency tree for installation (dependencies first).
     *
     * @return array<string>
     */
    private function flattenTreeForInstall(DependencyNode $node): array
    {
        $order = [];

        // Process children first (dependencies before dependents)
        foreach ($node->children as $child) {
            $order = array_merge($order, $this->flattenTreeForInstall($child));
        }

        $order[] = $node->name;

        return array_values(array_unique($order));
    }

    /**
     * Normalize domain name to full package format.
     */
    private function normalizeDomainName(string $name): string
    {
        if (! str_contains($name, '/')) {
            return "finaegis/{$name}";
        }

        return $name;
    }

    /**
     * Check if a version satisfies a constraint.
     */
    private function satisfiesVersion(string $version, string $constraint): bool
    {
        // Handle wildcard constraint
        if ($constraint === '*' || $constraint === '') {
            return true;
        }

        try {
            return Semver::satisfies($version, $constraint);
        } catch (Exception) {
            // If semver parsing fails, do simple comparison
            return str_starts_with($version, ltrim($constraint, '^~>=<'));
        }
    }
}
