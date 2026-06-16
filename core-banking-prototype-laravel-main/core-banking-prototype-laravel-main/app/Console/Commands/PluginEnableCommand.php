<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Infrastructure\Plugins\PluginManager;
use Illuminate\Console\Command;

class PluginEnableCommand extends Command
{
    protected $signature = 'plugin:enable {plugin : Plugin name (vendor/name)}';

    protected $description = 'Enable an installed plugin';

    public function handle(PluginManager $manager): int
    {
        $parts = explode('/', $this->argument('plugin'));
        if (count($parts) !== 2) {
            $this->error('Plugin name must be in vendor/name format.');

            return self::FAILURE;
        }

        [$vendor, $name] = $parts;
        $result = $manager->enable($vendor, $name);

        if ($result['success']) {
            $this->info($result['message']);

            return self::SUCCESS;
        }

        $this->error($result['message']);

        return self::FAILURE;
    }
}
