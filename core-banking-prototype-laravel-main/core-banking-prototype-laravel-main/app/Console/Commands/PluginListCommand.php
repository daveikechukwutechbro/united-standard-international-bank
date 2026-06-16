<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Infrastructure\Plugins\PluginManager;
use Illuminate\Console\Command;

class PluginListCommand extends Command
{
    protected $signature = 'plugin:list';

    protected $description = 'List all installed plugins';

    public function handle(PluginManager $manager): int
    {
        $plugins = $manager->list();

        if ($plugins->isEmpty()) {
            $this->info('No plugins installed.');

            return self::SUCCESS;
        }

        $this->table(
            ['Plugin', 'Version', 'Status', 'Installed'],
            $plugins->map(fn ($p) => [
                $p->getFullName(),
                $p->version,
                $p->status,
                $p->installed_at?->format('Y-m-d H:i') ?? '-',
            ])->toArray(),
        );

        return self::SUCCESS;
    }
}
