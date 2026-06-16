<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugins;

use App\Domain\Shared\Models\Plugin;
use Illuminate\Support\Facades\Log;

class PluginManager
{
    public function __construct(
        private readonly PluginLoader $loader,
        private readonly PluginDependencyResolver $dependencyResolver,
    ) {
    }

    /**
     * Install a plugin from a manifest.
     *
     * @return array{success: bool, message: string, plugin: Plugin|null}
     */
    public function install(PluginManifest $manifest): array
    {
        // Check if already installed
        $existing = Plugin::where('vendor', $manifest->vendor)
            ->where('name', $manifest->name)
            ->first();

        if ($existing) {
            return [
                'success' => false,
                'message' => "Plugin {$manifest->getFullName()} is already installed.",
                'plugin'  => $existing,
            ];
        }

        // Validate manifest
        if (! $manifest->validate()) {
            return [
                'success' => false,
                'message' => 'Invalid plugin manifest: vendor, name, and valid semver version are required.',
                'plugin'  => null,
            ];
        }

        // Resolve dependencies
        $deps = $this->dependencyResolver->resolve($manifest);
        if (! $deps['satisfied']) {
            $reasons = [];
            if (! empty($deps['missing'])) {
                $reasons[] = 'Missing dependencies: ' . implode(', ', $deps['missing']);
            }
            if ($deps['circular']) {
                $reasons[] = 'Circular dependency detected';
            }

            return [
                'success' => false,
                'message' => implode('. ', $reasons),
                'plugin'  => null,
            ];
        }

        $pluginPath = config('plugins.directory', base_path('plugins'))
            . "/{$manifest->vendor}/{$manifest->name}";

        $plugin = Plugin::create([
            'vendor'       => $manifest->vendor,
            'name'         => $manifest->name,
            'version'      => $manifest->version,
            'display_name' => $manifest->displayName,
            'description'  => $manifest->description,
            'author'       => $manifest->author,
            'license'      => $manifest->license,
            'homepage'     => $manifest->homepage,
            'status'       => 'inactive',
            'permissions'  => $manifest->permissions,
            'dependencies' => $manifest->dependencies,
            'path'         => $pluginPath,
            'entry_point'  => $manifest->entryPoint,
            'installed_at' => now(),
        ]);

        Log::info("Plugin installed: {$manifest->getFullName()}", [
            'version' => $manifest->version,
        ]);

        return [
            'success' => true,
            'message' => "Plugin {$manifest->getFullName()} installed successfully.",
            'plugin'  => $plugin,
        ];
    }

    /**
     * Remove a plugin.
     *
     * @return array{success: bool, message: string}
     */
    public function remove(string $vendor, string $name): array
    {
        $plugin = Plugin::where('vendor', $vendor)
            ->where('name', $name)
            ->first();

        if (! $plugin) {
            return [
                'success' => false,
                'message' => "Plugin {$vendor}/{$name} is not installed.",
            ];
        }

        if ($plugin->isSystem()) {
            return [
                'success' => false,
                'message' => "Cannot remove system plugin {$vendor}/{$name}.",
            ];
        }

        if ($plugin->isActive()) {
            $this->disable($vendor, $name);
        }

        $plugin->delete();

        Log::info("Plugin removed: {$vendor}/{$name}");

        return [
            'success' => true,
            'message' => "Plugin {$vendor}/{$name} removed successfully.",
        ];
    }

    /**
     * Enable a plugin.
     *
     * @return array{success: bool, message: string}
     */
    public function enable(string $vendor, string $name): array
    {
        $plugin = Plugin::where('vendor', $vendor)
            ->where('name', $name)
            ->first();

        if (! $plugin) {
            return [
                'success' => false,
                'message' => "Plugin {$vendor}/{$name} is not installed.",
            ];
        }

        if ($plugin->isActive()) {
            return [
                'success' => false,
                'message' => "Plugin {$vendor}/{$name} is already active.",
            ];
        }

        $plugin->update([
            'status'       => 'active',
            'activated_at' => now(),
        ]);

        $this->loader->bootPlugin($plugin->fresh());

        Log::info("Plugin enabled: {$vendor}/{$name}");

        return [
            'success' => true,
            'message' => "Plugin {$vendor}/{$name} enabled successfully.",
        ];
    }

    /**
     * Disable a plugin.
     *
     * @return array{success: bool, message: string}
     */
    public function disable(string $vendor, string $name): array
    {
        $plugin = Plugin::where('vendor', $vendor)
            ->where('name', $name)
            ->first();

        if (! $plugin) {
            return [
                'success' => false,
                'message' => "Plugin {$vendor}/{$name} is not installed.",
            ];
        }

        if (! $plugin->isActive()) {
            return [
                'success' => false,
                'message' => "Plugin {$vendor}/{$name} is not active.",
            ];
        }

        $plugin->update([
            'status'       => 'inactive',
            'activated_at' => null,
        ]);

        Log::info("Plugin disabled: {$vendor}/{$name}");

        return [
            'success' => true,
            'message' => "Plugin {$vendor}/{$name} disabled successfully.",
        ];
    }

    /**
     * Update a plugin to a new version.
     *
     * @return array{success: bool, message: string}
     */
    public function update(string $vendor, string $name, PluginManifest $manifest): array
    {
        $plugin = Plugin::where('vendor', $vendor)
            ->where('name', $name)
            ->first();

        if (! $plugin) {
            return [
                'success' => false,
                'message' => "Plugin {$vendor}/{$name} is not installed.",
            ];
        }

        $plugin->update([
            'version'         => $manifest->version,
            'description'     => $manifest->description,
            'permissions'     => $manifest->permissions,
            'dependencies'    => $manifest->dependencies,
            'last_updated_at' => now(),
        ]);

        Log::info("Plugin updated: {$vendor}/{$name}", [
            'version' => $manifest->version,
        ]);

        return [
            'success' => true,
            'message' => "Plugin {$vendor}/{$name} updated to {$manifest->version}.",
        ];
    }

    /**
     * List all installed plugins.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Plugin>
     */
    public function list(): \Illuminate\Database\Eloquent\Collection
    {
        return Plugin::orderBy('vendor')->orderBy('name')->get();
    }

    /**
     * Discover plugins from the filesystem and sync with database.
     *
     * @return array{discovered: int, new: int}
     */
    public function discover(): array
    {
        $manifests = $this->loader->discover();
        $newCount = 0;

        foreach ($manifests as $manifest) {
            $existing = Plugin::where('vendor', $manifest->vendor)
                ->where('name', $manifest->name)
                ->exists();

            if (! $existing) {
                $this->install($manifest);
                $newCount++;
            }
        }

        return [
            'discovered' => count($manifests),
            'new'        => $newCount,
        ];
    }
}
