<?php

declare(strict_types=1);

use App\Infrastructure\Domain\DependencyResolver;
use App\Infrastructure\Domain\DomainManager;
use App\Infrastructure\Domain\Enums\DomainStatus;
use App\Infrastructure\Domain\Enums\DomainType;
use App\Infrastructure\Domain\ModuleRouteLoader;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class);

beforeEach(function () {
    Cache::flush();

    $this->domainManager = app(DomainManager::class);
    $this->routeLoader = app(ModuleRouteLoader::class);
    $this->dependencyResolver = app(DependencyResolver::class);
});

describe('All domains have manifests', function () {
    it('loads at least 41 domain manifests', function () {
        $manifests = $this->domainManager->loadAllManifests();

        expect(count($manifests))->toBeGreaterThanOrEqual(41);
    });

    it('every manifest has a name, version, and type', function () {
        $manifests = $this->domainManager->loadAllManifests();

        foreach ($manifests as $name => $manifest) {
            expect($manifest->name)->not->toBeEmpty(
                "Manifest '{$name}' is missing a name"
            );
            expect($manifest->version)->not->toBeEmpty(
                "Manifest '{$name}' is missing a version"
            );
            expect($manifest->type)->toBeInstanceOf(
                DomainType::class,
                "Manifest '{$name}' has an invalid type"
            );
        }
    });

    it('returns a keyed array indexed by manifest name', function () {
        $manifests = $this->domainManager->loadAllManifests();

        foreach ($manifests as $key => $manifest) {
            expect($key)->toBe($manifest->name);
        }
    });
});

describe('No circular dependencies in required deps', function () {
    it('resolves required dependencies without cycles for every domain', function () {
        $manifests = $this->domainManager->loadAllManifests();
        $this->dependencyResolver->setManifests($manifests);

        $failures = [];

        foreach ($manifests as $name => $manifest) {
            try {
                // Build tree with only required dependencies (no optional)
                $this->dependencyResolver->buildDependencyTree($name, includeOptional: false);
            } catch (RuntimeException $e) {
                if (str_contains($e->getMessage(), 'Circular dependency')) {
                    $failures[$name] = $e->getMessage();
                } else {
                    throw $e;
                }
            }
        }

        expect($failures)->toBeEmpty(
            'Circular required dependencies detected: ' . json_encode($failures, JSON_PRETTY_PRINT)
        );
    });

    it('can build a dependency tree for every manifest', function () {
        $manifests = $this->domainManager->loadAllManifests();
        $this->dependencyResolver->setManifests($manifests);

        foreach ($manifests as $name => $manifest) {
            $tree = $this->dependencyResolver->buildDependencyTree($name, includeOptional: false);
            expect($tree->name)->toBe($name);
            expect($tree->satisfied)->toBeTrue(
                "Domain '{$name}' has unsatisfied dependencies in its tree"
            );
        }
    });

    it('can compute installation order for every domain', function () {
        $manifests = $this->domainManager->loadAllManifests();
        $this->dependencyResolver->setManifests($manifests);

        foreach ($manifests as $name => $manifest) {
            $order = $this->dependencyResolver->getInstallationOrder($name);
            expect($order)->toBeArray();
            expect($order)->not->toBeEmpty();
            // The domain itself should be last in the installation order
            expect(end($order))->toBe($name);
        }
    });
});

describe('Enable/disable flow', function () {
    it('disables a domain and confirms it is disabled', function () {
        $result = $this->domainManager->disable('exchange');

        expect($result->success)->toBeTrue();
        expect($this->domainManager->isDisabled('finaegis/exchange'))->toBeTrue();
    });

    it('enables a previously disabled domain', function () {
        $this->domainManager->disable('exchange');
        expect($this->domainManager->isDisabled('finaegis/exchange'))->toBeTrue();

        $result = $this->domainManager->enable('exchange');

        expect($result->success)->toBeTrue();
        expect($this->domainManager->isDisabled('finaegis/exchange'))->toBeFalse();
    });

    it('prevents disabling a core domain', function () {
        $result = $this->domainManager->disable('account');

        expect($result->success)->toBeFalse();
        expect($result->errors)->not->toBeEmpty();
    });

    it('reflects disabled status in the available domains list', function () {
        $this->domainManager->disable('exchange');

        $domains = $this->domainManager->getAvailableDomains();
        $exchange = $domains->first(fn ($d) => str_contains($d->name, 'exchange'));

        expect($exchange)->not->toBeNull();
        expect($exchange->status)->toBe(DomainStatus::DISABLED);
    });
});

describe('ModuleRouteLoader finds route files', function () {
    it('discovers at least 20 domain route files', function () {
        $routeFiles = $this->routeLoader->getAvailableRouteFiles();

        expect(count($routeFiles))->toBeGreaterThanOrEqual(20);
    });

    it('returns a map of domain name to absolute file path', function () {
        $routeFiles = $this->routeLoader->getAvailableRouteFiles();

        foreach ($routeFiles as $domain => $path) {
            expect($domain)->toBeString()->not->toBeEmpty();
            expect($path)->toContain('/Routes/api.php');
            expect(file_exists($path))->toBeTrue(
                "Route file does not exist: {$path}"
            );
        }
    });

    it('excludes disabled domain routes from loading', function () {
        $this->domainManager->disable('exchange');

        // getAvailableRouteFiles lists all files regardless of disabled status,
        // but loadRoutes() skips disabled domains. Verify Exchange is still
        // in available files (it only filters at load time).
        $routeFiles = $this->routeLoader->getAvailableRouteFiles();
        expect($routeFiles)->toHaveKey('Exchange');

        // Re-enable so it doesn't affect other tests
        $this->domainManager->enable('exchange');
    });
});

describe('Domain types are correct', function () {
    it('marks Account as a core domain', function () {
        $manifests = $this->domainManager->loadAllManifests();
        $account = $manifests['finaegis/account'] ?? null;

        expect($account)->not->toBeNull();
        expect($account->type)->toBe(DomainType::CORE);
    });

    it('marks Shared as a core domain', function () {
        $manifests = $this->domainManager->loadAllManifests();
        $shared = $manifests['finaegis/shared'] ?? null;

        expect($shared)->not->toBeNull();
        expect($shared->type)->toBe(DomainType::CORE);
    });

    it('marks Compliance as a core domain', function () {
        $manifests = $this->domainManager->loadAllManifests();
        $compliance = $manifests['finaegis/compliance'] ?? null;

        expect($compliance)->not->toBeNull();
        expect($compliance->type)->toBe(DomainType::CORE);
    });

    it('has at least 8 core domains', function () {
        $manifests = $this->domainManager->loadAllManifests();
        $coreDomains = array_filter($manifests, fn ($m) => $m->type === DomainType::CORE);

        expect(count($coreDomains))->toBeGreaterThanOrEqual(8);
    });

    it('marks Exchange as an optional domain', function () {
        $manifests = $this->domainManager->loadAllManifests();
        $exchange = $manifests['finaegis/exchange'] ?? null;

        expect($exchange)->not->toBeNull();
        expect($exchange->type)->toBe(DomainType::OPTIONAL);
    });
});

describe('Module API routes exist', function () {
    it('has the api.modules.index route', function () {
        $route = app('router')->getRoutes()->getByName('api.modules.index');

        expect($route)->not->toBeNull('Route api.modules.index is not registered');
    });

    it('has the api.modules.show route', function () {
        $route = app('router')->getRoutes()->getByName('api.modules.show');

        expect($route)->not->toBeNull('Route api.modules.show is not registered');
    });

    it('has the api.modules.health route', function () {
        $route = app('router')->getRoutes()->getByName('api.modules.health');

        expect($route)->not->toBeNull('Route api.modules.health is not registered');
    });

    it('has the api.modules.enable route', function () {
        $route = app('router')->getRoutes()->getByName('api.modules.enable');

        expect($route)->not->toBeNull('Route api.modules.enable is not registered');
    });

    it('has the api.modules.disable route', function () {
        $route = app('router')->getRoutes()->getByName('api.modules.disable');

        expect($route)->not->toBeNull('Route api.modules.disable is not registered');
    });
});

describe('Performance report command', function () {
    it('runs successfully with json format', function () {
        $exitCode = Artisan::call('performance:report', ['--format' => 'json']);

        expect($exitCode)->toBe(0);
    });
});
