<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugins;

use App\Domain\Shared\Models\Plugin;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class PluginLoader
{
    /**
     * Discover plugins from the plugins directory.
     *
     * @return array<string, PluginManifest>
     */
    public function discover(): array
    {
        $pluginsDir = config('plugins.directory', base_path('plugins'));
        $manifests = [];

        if (! File::isDirectory($pluginsDir)) {
            return $manifests;
        }

        foreach (File::directories($pluginsDir) as $vendorDir) {
            foreach (File::directories($vendorDir) as $pluginDir) {
                $manifestPath = $pluginDir . '/plugin.json';
                if (File::exists($manifestPath)) {
                    try {
                        $manifest = PluginManifest::fromFile($manifestPath);
                        if ($manifest->validate()) {
                            $manifests[$manifest->getFullName()] = $manifest;
                        }
                    } catch (Throwable $e) {
                        Log::warning("Failed to load plugin manifest: {$manifestPath}", [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        return $manifests;
    }

    /**
     * Boot all active plugins by registering their service providers.
     */
    public function bootActivePlugins(): void
    {
        $plugins = Plugin::where('status', 'active')->get();

        foreach ($plugins as $plugin) {
            $this->bootPlugin($plugin);
        }
    }

    /**
     * Boot a single plugin.
     */
    public function bootPlugin(Plugin $plugin): void
    {
        if (! $plugin->entry_point || ! $plugin->path) {
            return;
        }

        $providerClass = $this->resolveProviderClass($plugin);

        if (! class_exists($providerClass)) {
            Log::warning("Plugin service provider not found: {$providerClass}", [
                'plugin' => $plugin->getFullName(),
            ]);

            return;
        }

        if (! is_subclass_of($providerClass, \Illuminate\Support\ServiceProvider::class)) {
            Log::warning("Plugin entry point is not a ServiceProvider: {$providerClass}", [
                'plugin' => $plugin->getFullName(),
            ]);

            return;
        }

        try {
            app()->register($providerClass);
        } catch (Throwable $e) {
            Log::error("Failed to boot plugin: {$plugin->getFullName()}", [
                'error' => $e->getMessage(),
            ]);

            $plugin->update(['status' => 'failed']);
        }
    }

    private function resolveProviderClass(Plugin $plugin): string
    {
        if (str_contains($plugin->entry_point ?? '', '\\')) {
            return $plugin->entry_point;
        }

        return "Plugins\\{$plugin->vendor}\\{$plugin->name}\\{$plugin->entry_point}";
    }
}
