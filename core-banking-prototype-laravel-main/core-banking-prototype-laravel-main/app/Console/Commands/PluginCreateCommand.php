<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PluginCreateCommand extends Command
{
    protected $signature = 'plugin:create {vendor : Plugin vendor name} {name : Plugin name}';

    protected $description = 'Create a new plugin scaffold';

    public function handle(): int
    {
        $vendor = $this->argument('vendor');
        $name = $this->argument('name');

        $namePattern = '/^[a-zA-Z0-9_-]+$/';
        if (
            ! is_string($vendor) || ! preg_match($namePattern, $vendor)
            || ! is_string($name) || ! preg_match($namePattern, $name)
        ) {
            $this->error('Vendor and name must contain only alphanumeric characters, hyphens, and underscores.');

            return self::FAILURE;
        }

        $pluginsDir = config('plugins.directory', base_path('plugins'));
        $pluginPath = "{$pluginsDir}/{$vendor}/{$name}";

        if (File::isDirectory($pluginPath)) {
            $this->error("Plugin directory already exists: {$pluginPath}");

            return self::FAILURE;
        }

        // Create directory structure
        File::makeDirectory("{$pluginPath}/src", 0755, true);
        File::makeDirectory("{$pluginPath}/routes", 0755, true);
        File::makeDirectory("{$pluginPath}/config", 0755, true);
        File::makeDirectory("{$pluginPath}/migrations", 0755, true);
        File::makeDirectory("{$pluginPath}/tests", 0755, true);

        // Create plugin.json manifest
        $manifest = json_encode([
            'vendor'       => $vendor,
            'name'         => $name,
            'version'      => '1.0.0',
            'display_name' => ucfirst($name),
            'description'  => "A FinAegis plugin: {$vendor}/{$name}",
            'author'       => $vendor,
            'license'      => 'MIT',
            'entry_point'  => 'ServiceProvider',
            'permissions'  => [],
            'dependencies' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';

        File::put("{$pluginPath}/plugin.json", $manifest);

        // Create ServiceProvider stub
        $namespace = 'Plugins\\' . ucfirst($vendor) . '\\' . ucfirst($name);
        $provider = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
PHP;

        File::put("{$pluginPath}/src/ServiceProvider.php", $provider);

        $this->info("Plugin scaffold created at: {$pluginPath}");
        $this->line('  - plugin.json (manifest)');
        $this->line('  - src/ServiceProvider.php');
        $this->line('  - routes/, config/, migrations/, tests/');

        return self::SUCCESS;
    }
}
