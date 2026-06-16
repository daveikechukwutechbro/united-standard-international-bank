<?php

declare(strict_types=1);

namespace Plugins\AuditExporter;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AuditExportCommand extends Command
{
    protected $signature = 'audit:export
                            {--format=json : Export format (json, csv)}
                            {--days=90 : Number of days to export}
                            {--output= : Output file path}';

    protected $description = 'Export audit logs to file';

    public function handle(): int
    {
        $format = $this->option('format');
        $days = (int) $this->option('days');
        $outputPath = $this->option('output')
            ?? config('plugins.audit-exporter.export_path', storage_path('exports/audit'));

        $this->info("Exporting audit logs for the last {$days} days...");

        $logs = DB::table('activity_log')
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();

        if ($logs->isEmpty()) {
            $this->warn('No audit logs found for the specified period.');

            return Command::SUCCESS;
        }

        $filename = 'audit_export_' . now()->format('Y-m-d_His');

        if (! File::isDirectory($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        if ($format === 'csv') {
            $filePath = $outputPath . '/' . $filename . '.csv';
            $this->exportCsv($logs, $filePath);
        } else {
            $filePath = $outputPath . '/' . $filename . '.json';
            $this->exportJson($logs, $filePath);
        }

        $this->info("Exported {$logs->count()} audit records to: {$filePath}");

        return Command::SUCCESS;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \stdClass>  $logs
     */
    private function exportJson($logs, string $filePath): void
    {
        File::put($filePath, $logs->toJson(JSON_PRETTY_PRINT));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \stdClass>  $logs
     */
    private function exportCsv($logs, string $filePath): void
    {
        $handle = fopen($filePath, 'w');
        if (! $handle) {
            return;
        }

        $first = $logs->first();
        if ($first) {
            fputcsv($handle, array_keys((array) $first));
        }

        foreach ($logs as $log) {
            fputcsv($handle, array_values((array) $log));
        }

        fclose($handle);
    }
}
