<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class GeneratePerformanceReport extends Command
{
    protected $signature = 'performance:report
                            {--format=json : Output format (json or markdown)}';

    protected $description = 'Generate a performance baseline report';

    public function handle(): int
    {
        $format = $this->option('format');

        if (! in_array($format, ['json', 'markdown'], true)) {
            $this->error("Invalid format: {$format}. Use 'json' or 'markdown'.");

            return Command::FAILURE;
        }

        $this->info('Collecting performance metrics...');

        $metrics = $this->collectMetrics();

        $date = now()->format('Y-m-d');
        $directory = storage_path('app/benchmarks');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if ($format === 'markdown') {
            $output = $this->formatMarkdown($metrics);
            $filePath = "{$directory}/performance-report-{$date}.md";
        } else {
            $output = (string) json_encode($metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $filePath = "{$directory}/performance-report-{$date}.json";
        }

        $this->line($output);

        File::put($filePath, $output);

        $this->newLine();
        $this->info("Report saved to: {$filePath}");

        return Command::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function collectMetrics(): array
    {
        $routes = Route::getRoutes();
        $routeCount = count($routes->getRoutes());

        $middleware = [];
        /** @var RoutingRoute $route */
        foreach ($routes->getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $mw) {
                $middleware[$mw] = true;
            }
        }
        $middlewareCount = count($middleware);

        $modelCount = count(glob(app_path('Models/*.php')) ?: []);
        $domainCount = count(array_filter(
            glob(app_path('Domain/*'), GLOB_ONLYDIR) ?: [],
            'is_dir'
        ));
        $migrationCount = count(glob(database_path('migrations/*.php')) ?: []);

        $configCached = file_exists(base_path('bootstrap/cache/config.php'));
        $routeCached = file_exists(base_path('bootstrap/cache/routes-v7.php'));

        return [
            'generated_at'     => now()->toIso8601String(),
            'route_count'      => $routeCount,
            'middleware_count' => $middlewareCount,
            'model_count'      => $modelCount,
            'domain_count'     => $domainCount,
            'migration_count'  => $migrationCount,
            'config_cached'    => $configCached,
            'route_cached'     => $routeCached,
            'php_version'      => PHP_VERSION,
            'laravel_version'  => app()->version(),
            'memory_limit'     => ini_get('memory_limit'),
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function formatMarkdown(array $metrics): string
    {
        $configCached = $metrics['config_cached'] ? 'Yes' : 'No';
        $routeCached = $metrics['route_cached'] ? 'Yes' : 'No';

        return <<<MARKDOWN
# Performance Baseline Report

**Generated at:** {$metrics['generated_at']}

## Environment

| Metric | Value |
|--------|-------|
| PHP Version | {$metrics['php_version']} |
| Laravel Version | {$metrics['laravel_version']} |
| Memory Limit | {$metrics['memory_limit']} |

## Application Metrics

| Metric | Value |
|--------|-------|
| Route Count | {$metrics['route_count']} |
| Middleware Count | {$metrics['middleware_count']} |
| Model Count | {$metrics['model_count']} |
| Domain Count | {$metrics['domain_count']} |
| Migration Count | {$metrics['migration_count']} |

## Cache Status

| Cache | Status |
|-------|--------|
| Config Cache | {$configCached} |
| Route Cache | {$routeCached} |
MARKDOWN;
    }
}
