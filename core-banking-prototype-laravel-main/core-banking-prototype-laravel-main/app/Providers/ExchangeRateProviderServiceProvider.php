<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Asset\Services\ExchangeRateService;
use App\Domain\Exchange\Providers\FixerIoProvider;
use App\Domain\Exchange\Providers\MockExchangeRateProvider;
use App\Domain\Exchange\Services\EnhancedExchangeRateService;
use App\Domain\Exchange\Services\ExchangeRateProviderRegistry;
use Illuminate\Support\ServiceProvider;

class ExchangeRateProviderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the provider registry as singleton
        $this->app->singleton(ExchangeRateProviderRegistry::class);

        // Bind the enhanced service with explicit resolution
        $this->app->singleton(
            EnhancedExchangeRateService::class,
            function ($app) {
                return new EnhancedExchangeRateService($app->make(ExchangeRateProviderRegistry::class));
            }
        );

        // Bind the enhanced service as the default exchange rate service
        $this->app->bind(ExchangeRateService::class, EnhancedExchangeRateService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $registry = app(ExchangeRateProviderRegistry::class);

        // Register mock provider
        if (config('exchange.providers.mock.enabled', true)) {
            $mockProvider = new MockExchangeRateProvider(config('exchange.providers.mock', []));
            $registry->register('mock', $mockProvider);
        }

        // Register Fixer.io provider
        if (config('exchange.providers.fixer.enabled', false)) {
            $fixerProvider = new FixerIoProvider(config('exchange.providers.fixer', []));
            $registry->register('fixer', $fixerProvider);
        }

        // Register other providers from config
        foreach (config('exchange.providers', []) as $name => $config) {
            if (in_array($name, ['mock', 'fixer'])) {
                continue; // Already handled above
            }

            if (! ($config['enabled'] ?? false)) {
                continue;
            }

            $providerClass = $config['class'] ?? null;

            if ($providerClass && class_exists($providerClass)) {
                $provider = new $providerClass($config);
                $registry->register($name, $provider);
            }
        }

        // Set default provider
        if ($default = config('exchange.default_provider')) {
            $registry->setDefault($default);
        }

        // Schedule rate refresh if enabled
        if (config('exchange.auto_refresh.enabled', false)) {
            $this->scheduleRateRefresh();
        }
    }

    /**
     * Schedule automatic rate refresh.
     */
    private function scheduleRateRefresh(): void
    {
        $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

        $frequency = config('exchange.auto_refresh.frequency', 'hourly');

        $command = $schedule->job(new \App\Domain\Exchange\Jobs\RefreshExchangeRatesJob());

        switch ($frequency) {
            case 'every_minute':
                $command->everyMinute();
                break;
            case 'every_five_minutes':
                $command->everyFiveMinutes();
                break;
            case 'every_fifteen_minutes':
                $command->everyFifteenMinutes();
                break;
            case 'every_thirty_minutes':
                $command->everyThirtyMinutes();
                break;
            case 'hourly':
                $command->hourly();
                break;
            case 'daily':
                $command->daily();
                break;
            default:
                $command->hourly();
        }
    }
}
