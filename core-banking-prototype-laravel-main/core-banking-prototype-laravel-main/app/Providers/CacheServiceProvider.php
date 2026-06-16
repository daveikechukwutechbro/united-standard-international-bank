<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Account\Services\Cache\AccountCacheService;
use App\Domain\Account\Services\Cache\CacheManager;
use App\Domain\Account\Services\Cache\TransactionCacheService;
use App\Domain\Account\Services\Cache\TurnoverCacheService;
use Illuminate\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register cache services as singletons
        $this->app->singleton(AccountCacheService::class);
        $this->app->singleton(TransactionCacheService::class);
        $this->app->singleton(TurnoverCacheService::class);

        // Register the cache manager
        $this->app->singleton(
            CacheManager::class,
            function ($app) {
                return new CacheManager(
                    $app->make(AccountCacheService::class),
                    $app->make(TransactionCacheService::class),
                    $app->make(TurnoverCacheService::class)
                );
            }
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only override to Redis if cache is still at default (database)
        // and Redis connection is configured
        if (config('cache.default') === 'database' && config('database.redis.default')) {
            config(['cache.default' => 'redis']);
        }
    }
}
