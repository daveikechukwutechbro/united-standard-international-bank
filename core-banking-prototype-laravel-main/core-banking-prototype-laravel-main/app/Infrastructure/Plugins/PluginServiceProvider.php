<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugins;

use Illuminate\Support\ServiceProvider;
use Spatie\EventSourcing\Facades\Projectionist;
use Throwable;

class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            config_path('plugins.php'),
            'plugins',
        );

        $this->app->singleton(PluginHookManager::class);
        $this->app->singleton(PluginSecurityScanner::class);
        $this->app->singleton(PluginSandbox::class);
        $this->app->singleton(PluginDependencyResolver::class);
        $this->app->singleton(PluginLoader::class);
        $this->app->singleton(PluginManager::class, function ($app) {
            return new PluginManager(
                $app->make(PluginLoader::class),
                $app->make(PluginDependencyResolver::class),
            );
        });
    }

    public function boot(): void
    {
        Projectionist::addReactor(PluginHookReactor::class);

        if (config('plugins.auto_discover', true) && ! $this->app->runningInConsole()) {
            try {
                $this->app->make(PluginLoader::class)->bootActivePlugins();
            } catch (Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Plugin auto-discovery failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
