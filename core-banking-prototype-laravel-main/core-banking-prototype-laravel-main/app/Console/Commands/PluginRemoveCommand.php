<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Infrastructure\Plugins\PluginManager;
use Illuminate\Console\Command;

class PluginRemoveCommand extends Command
{
    protected $signature = 'plugin:remove {plugin : Plugin name (vendor/name)}';

    protected $description = 'Remove an installed plugin';

    public function handle(PluginManager $manager): int
    {
        $parts = explode('/', $this->argument('plugin'));
        if (count($parts) !== 2) {
            $this->error('Plugin name must be in vendor/name format.');

            return self::FAILURE;
        }

        [$vendor, $name] = $parts;

        if (! $this->confirm("Remove plugin {$vendor}/{$name}?")) {
            return self::SUCCESS;
        }

        $result = $manager->remove($vendor, $name);

        if ($result['success']) {
            $this->info($result['message']);

            return self::SUCCESS;
        }

        $this->error($result['message']);

        return self::FAILURE;
    }
}
