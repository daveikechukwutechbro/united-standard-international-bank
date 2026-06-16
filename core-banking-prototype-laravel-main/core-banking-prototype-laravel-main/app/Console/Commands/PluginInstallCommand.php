<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Infrastructure\Plugins\PluginManager;
use App\Infrastructure\Plugins\PluginManifest;
use Illuminate\Console\Command;

class PluginInstallCommand extends Command
{
    protected $signature = 'plugin:install {path : Path to the plugin directory}';

    protected $description = 'Install a plugin from a directory';

    public function handle(PluginManager $manager): int
    {
        $path = $this->argument('path');
        $manifestPath = rtrim($path, '/') . '/plugin.json';

        if (! file_exists($manifestPath)) {
            $this->error("Plugin manifest not found at: {$manifestPath}");

            return self::FAILURE;
        }

        $manifest = PluginManifest::fromFile($manifestPath);

        $this->info("Installing plugin: {$manifest->getFullName()} v{$manifest->version}");

        $result = $manager->install($manifest);

        if ($result['success']) {
            $this->info($result['message']);

            return self::SUCCESS;
        }

        $this->error($result['message']);

        return self::FAILURE;
    }
}
