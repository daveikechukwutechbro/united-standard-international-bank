<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Shared\EventSourcing\EventUpcastingService;
use Illuminate\Console\Command;

class EventVersionListCommand extends Command
{
    protected $signature = 'event:versions
                            {--format=table : Output format (table or json)}';

    protected $description = 'List all event versions and registered upcasters';

    public function handle(EventUpcastingService $upcastingService): int
    {
        $registry = $upcastingService->getRegistry();
        $versions = $registry->getAllVersions();
        $format = is_string($this->option('format')) ? $this->option('format') : 'table';

        if (empty($versions)) {
            $this->info('No event versions registered.');

            return self::SUCCESS;
        }

        if ($format === 'json') {
            $this->line(json_encode($versions, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($versions as $eventClass => $info) {
            $rows[] = [
                $eventClass,
                $info['current_version'],
                $info['upcaster_count'],
            ];
        }

        $this->table(['Event Class', 'Current Version', 'Upcasters'], $rows);

        return self::SUCCESS;
    }
}
