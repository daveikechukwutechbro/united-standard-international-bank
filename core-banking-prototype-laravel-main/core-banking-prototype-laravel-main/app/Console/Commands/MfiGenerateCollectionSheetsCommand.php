<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Microfinance\Models\FieldOfficer;
use App\Domain\Microfinance\Models\Group;
use App\Domain\Microfinance\Services\FieldOfficerService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class MfiGenerateCollectionSheetsCommand extends Command
{
    protected $signature = 'mfi:generate-collection-sheets {--date= : Collection date (YYYY-MM-DD), defaults to today}';

    protected $description = 'Generate collection sheets for all active field officers and their groups';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(FieldOfficerService $fieldOfficerService): int
    {
        $dateOption = $this->option('date');
        $collectionDate = $dateOption !== null
            ? Carbon::parse((string) $dateOption)->toDateString()
            : Carbon::today()->toDateString();

        $this->info("Generating collection sheets for date: {$collectionDate}");

        $officers = FieldOfficer::active()->get();

        if ($officers->isEmpty()) {
            $this->warn('No active field officers found.');

            return Command::SUCCESS;
        }

        $this->info("Found {$officers->count()} active field officer(s).");

        $groups = Group::active()->get();

        if ($groups->isEmpty()) {
            $this->warn('No active groups found.');

            return Command::SUCCESS;
        }

        $generated = 0;
        $failed = 0;
        $rows = [];

        foreach ($officers as $officer) {
            foreach ($groups as $group) {
                try {
                    $sheet = $fieldOfficerService->generateCollectionSheet(
                        officerId: $officer->id,
                        groupId: $group->id,
                        collectionDate: $collectionDate,
                        expectedAmount: '0.00',
                    );

                    $rows[] = [$sheet->id, $officer->name, $group->name, $collectionDate, 'pending'];
                    $generated++;
                } catch (Throwable $e) {
                    $this->warn("Failed for officer {$officer->name} / group {$group->name}: {$e->getMessage()}");
                    $failed++;
                }
            }
        }

        if (count($rows) > 0) {
            $this->table(['Sheet ID', 'Officer', 'Group', 'Date', 'Status'], $rows);
        }

        $this->info("Generated: {$generated} sheet(s). Failed: {$failed}.");

        return $failed === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
