<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Microfinance\Services\GroupLendingService;
use Illuminate\Console\Command;
use Throwable;

class MfiCreateGroupCommand extends Command
{
    protected $signature = 'mfi:create-group {name : Group name} {--frequency=weekly : Meeting frequency (daily|weekly|biweekly|monthly)} {--center= : Center/branch name} {--meeting-day= : Day of the week for meetings}';

    protected $description = 'Create a new microfinance joint liability group';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(GroupLendingService $groupLendingService): int
    {
        $name = (string) $this->argument('name');
        $frequency = (string) ($this->option('frequency') ?? 'weekly');
        $center = $this->option('center') !== null ? (string) $this->option('center') : null;
        $meetingDay = $this->option('meeting-day') !== null ? (string) $this->option('meeting-day') : null;

        $this->info("Creating group: {$name}");
        $this->line("  Frequency : {$frequency}");

        if ($center !== null) {
            $this->line("  Center    : {$center}");
        }

        if ($meetingDay !== null) {
            $this->line("  Meeting day: {$meetingDay}");
        }

        try {
            $group = $groupLendingService->createGroup(
                name: $name,
                meetingFrequency: $frequency,
                centerName: $center,
                meetingDay: $meetingDay,
            );

            $this->info('Group created successfully.');
            $this->table(
                ['ID', 'Name', 'Status', 'Frequency'],
                [[$group->id, $group->name, $group->status->value, $group->meeting_frequency->value]],
            );

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Failed to create group: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
