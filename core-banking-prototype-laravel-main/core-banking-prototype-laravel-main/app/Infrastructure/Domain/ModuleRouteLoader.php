<?php

declare(strict_types=1);

namespace App\Infrastructure\Domain;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * Loads domain-specific route files based on module manifests.
 *
 * Each domain can define a Routes/api.php file that is loaded
 * automatically if the domain is not disabled. The loader respects
 * the modules.disabled configuration to skip routes for inactive domains.
 */
class ModuleRouteLoader
{
    public function __construct(
        private readonly DomainManager $domainManager,
        private readonly string $domainBasePath = 'app/Domain',
    ) {
    }

    /**
     * Load all enabled domain route files.
     *
     * Scans each domain directory for Routes/api.php and loads it
     * unless the domain is disabled via config/modules.php.
     */
    public function loadRoutes(): void
    {
        $basePath = base_path($this->domainBasePath);

        if (! File::isDirectory($basePath)) {
            return;
        }

        foreach (File::directories($basePath) as $domainDir) {
            $domainName = basename($domainDir);

            if ($this->domainManager->isDisabled($domainName)) {
                continue;
            }

            $routeFile = "{$domainDir}/Routes/api.php";
            if (File::exists($routeFile)) {
                require $routeFile;
            }
        }
    }

    /**
     * Load routes for a specific domain.
     */
    public function loadDomainRoutes(string $domain): void
    {
        $basePath = base_path($this->domainBasePath);
        $routeFile = "{$basePath}/{$domain}/Routes/api.php";

        if (File::exists($routeFile)) {
            require $routeFile;
        }
    }

    /**
     * Get all domain route files that exist.
     *
     * @return array<string, string> Map of domain name to route file path
     */
    public function getAvailableRouteFiles(): array
    {
        $basePath = base_path($this->domainBasePath);
        $files = [];

        if (! File::isDirectory($basePath)) {
            return $files;
        }

        foreach (File::directories($basePath) as $domainDir) {
            $routeFile = "{$domainDir}/Routes/api.php";
            if (File::exists($routeFile)) {
                $files[basename($domainDir)] = $routeFile;
            }
        }

        return $files;
    }
}
