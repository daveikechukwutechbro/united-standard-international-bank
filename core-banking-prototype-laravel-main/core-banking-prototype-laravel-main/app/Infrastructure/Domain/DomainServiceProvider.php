<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain;

use App\Domain\Shared\EventSourcing\EventRouter;
use App\Domain\Shared\EventSourcing\EventRouterInterface;
use App\Domain\Shared\EventSourcing\EventUpcastingService;
use App\Domain\Shared\EventSourcing\EventVersionRegistry;
use App\Infrastructure\Domain\Commands\DomainCreateCommand;
use App\Infrastructure\Domain\Commands\DomainDependenciesCommand;
use App\Infrastructure\Domain\Commands\DomainDisableCommand;
use App\Infrastructure\Domain\Commands\DomainEnableCommand;
use App\Infrastructure\Domain\Commands\DomainInstallCommand;
use App\Infrastructure\Domain\Commands\DomainListCommand;
use App\Infrastructure\Domain\Commands\DomainRemoveCommand;
use App\Infrastructure\Domain\Commands\DomainVerifyCommand;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Domain Management infrastructure.
 *
 * Registers the DomainManager and related services for
 * the modular architecture system.
 */
class DomainServiceProvider extends ServiceProvider
{
    /**
     * The commands to register.
     *
     * @var array<class-string>
     */
    protected array $commands = [
        DomainCreateCommand::class,
        DomainListCommand::class,
        DomainInstallCommand::class,
        DomainRemoveCommand::class,
        DomainVerifyCommand::class,
        DomainDependenciesCommand::class,
        DomainEnableCommand::class,
        DomainDisableCommand::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(DependencyResolver::class);

        $this->app->singleton(DomainManager::class, function ($app) {
            return new DomainManager(
                dependencyResolver: $app->make(DependencyResolver::class),
                domainBasePath: 'app/Domain',
            );
        });

        $this->app->singleton(ModuleRouteLoader::class, function ($app) {
            return new ModuleRouteLoader(
                domainManager: $app->make(DomainManager::class),
                domainBasePath: 'app/Domain',
            );
        });

        $this->registerEventRouter();
        $this->registerEventVersioning();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }

    /**
     * Register the Event Router for domain-based event table partitioning.
     */
    private function registerEventRouter(): void
    {
        $this->app->singleton(EventRouterInterface::class, function () {
            $customTables = (array) config('event-store.routing.domain_tables', []);
            $defaultTable = (string) config('event-store.routing.default_table', 'stored_events');

            return new EventRouter(
                domainTableMap: $customTables,
                defaultTable: $defaultTable,
            );
        });
    }

    /**
     * Register the Event Versioning and Upcasting services.
     */
    private function registerEventVersioning(): void
    {
        $this->app->singleton(EventVersionRegistry::class);

        $this->app->singleton(EventUpcastingService::class, function ($app) {
            return new EventUpcastingService(
                registry: $app->make(EventVersionRegistry::class),
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<class-string>
     */
    public function provides(): array
    {
        return [
            DependencyResolver::class,
            DomainManager::class,
            ModuleRouteLoader::class,
        ];
    }
}
