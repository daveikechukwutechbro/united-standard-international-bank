<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Compliance\Services\Certification\DataProcessingRegisterService;
use Illuminate\Console\Command;

class GdprRegisterExportCommand extends Command
{
    protected $signature = 'gdpr:register-export
        {--format=json : Export format (json, text)}
        {--completeness : Show completeness check instead of full export}';

    protected $description = 'Export GDPR Article 30 Records of Processing Activities (ROPA)';

    public function handle(DataProcessingRegisterService $registerService): int
    {
        $format = $this->option('format');

        if ($this->option('completeness')) {
            return $this->showCompleteness($registerService, $format);
        }

        $this->info('GDPR Article 30 — Records of Processing Activities');
        $this->info('===================================================');
        $this->newLine();

        $register = $registerService->exportRegister();

        if ($format === 'json') {
            $this->line((string) json_encode($register, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        $this->line("Controller: {$register['controller']}");
        $this->line("DPO Contact: {$register['dpo_contact']}");
        $this->line("Total Activities: {$register['total_activities']}");
        $this->newLine();

        foreach ($register['activities'] as $i => $activity) {
            $num = $i + 1;
            $this->info("[{$num}] {$activity['name']}");
            $this->line("    Purpose: {$activity['purpose']}");
            $this->line("    Legal Basis: {$activity['legal_basis']}");
            $this->line("    Status: {$activity['status']}");
            $this->newLine();
        }

        return Command::SUCCESS;
    }

    private function showCompleteness(DataProcessingRegisterService $registerService, string $format): int
    {
        $completeness = $registerService->checkCompleteness();

        if ($format === 'json') {
            $this->line((string) json_encode($completeness, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        $this->info('Register Completeness Check');
        $this->info('===========================');
        $this->newLine();

        $rate = $completeness['completeness_rate'];
        $color = $rate >= 90 ? 'green' : ($rate >= 70 ? 'yellow' : 'red');

        $this->line("Total: {$completeness['total']}");
        $this->line("Complete: {$completeness['complete']}");
        $this->line("Incomplete: {$completeness['incomplete']}");
        $this->line("Completeness: <fg={$color}>{$rate}%</>");

        return Command::SUCCESS;
    }
}
