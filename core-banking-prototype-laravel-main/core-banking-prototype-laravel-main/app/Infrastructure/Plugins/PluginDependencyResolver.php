<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugins;

use App\Domain\Shared\Models\Plugin;

class PluginDependencyResolver
{
    /**
     * Resolve dependencies for a plugin manifest, checking for missing and circular dependencies.
     *
     * @param  PluginManifest  $manifest
     * @return array{satisfied: bool, missing: array<string>, circular: bool}
     */
    public function resolve(PluginManifest $manifest): array
    {
        $missing = [];

        foreach ($manifest->dependencies as $dep => $constraint) {
            $installed = Plugin::where('vendor', $this->extractVendor($dep))
                ->where('name', $this->extractName($dep))
                ->where('status', 'active')
                ->first();

            if (! $installed) {
                $missing[] = $dep;
                continue;
            }

            if (! $this->satisfiesConstraint($installed->version, $constraint)) {
                $missing[] = "{$dep} (requires {$constraint}, installed: {$installed->version})";
            }
        }

        $circular = $this->detectCircularDependencies($manifest);

        return [
            'satisfied' => empty($missing) && ! $circular,
            'missing'   => $missing,
            'circular'  => $circular,
        ];
    }

    /**
     * Check if the installed version satisfies the constraint.
     */
    public function satisfiesConstraint(string $installed, string $constraint): bool
    {
        // Support basic semver constraints: ^, ~, >=, exact
        $constraint = trim($constraint);

        if (str_starts_with($constraint, '^')) {
            $required = ltrim($constraint, '^');

            return version_compare($installed, $required, '>=')
                && version_compare($installed, $this->nextMajor($required), '<');
        }

        if (str_starts_with($constraint, '~')) {
            $required = ltrim($constraint, '~');

            return version_compare($installed, $required, '>=')
                && version_compare($installed, $this->nextMinor($required), '<');
        }

        if (str_starts_with($constraint, '>=')) {
            $required = trim(substr($constraint, 2));

            return version_compare($installed, $required, '>=');
        }

        return version_compare($installed, $constraint, '==');
    }

    /**
     * Detect circular dependencies.
     */
    private function detectCircularDependencies(PluginManifest $manifest): bool
    {
        return $this->hasCycle($manifest->getFullName(), $manifest->dependencies, []);
    }

    /**
     * @param  array<string>  $visited
     * @param  array<string, string>  $dependencies
     */
    private function hasCycle(string $current, array $dependencies, array $visited): bool
    {
        if (in_array($current, $visited, true)) {
            return true;
        }

        $visited[] = $current;

        foreach (array_keys($dependencies) as $dep) {
            $plugin = Plugin::where('vendor', $this->extractVendor($dep))
                ->where('name', $this->extractName($dep))
                ->first();

            if ($plugin && ! empty($plugin->dependencies)) {
                if ($this->hasCycle($dep, $plugin->dependencies, $visited)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function nextMajor(string $version): string
    {
        $parts = explode('.', $version);

        return ((int) $parts[0] + 1) . '.0.0';
    }

    private function nextMinor(string $version): string
    {
        $parts = explode('.', $version);

        return $parts[0] . '.' . ((int) ($parts[1] ?? 0) + 1) . '.0';
    }

    private function extractVendor(string $fullName): string
    {
        return explode('/', $fullName)[0] ?? $fullName;
    }

    private function extractName(string $fullName): string
    {
        $parts = explode('/', $fullName);

        return $parts[1] ?? $parts[0];
    }
}
