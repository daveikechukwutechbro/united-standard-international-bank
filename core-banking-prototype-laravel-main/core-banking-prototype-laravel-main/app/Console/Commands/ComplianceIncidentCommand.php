<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Compliance\Services\Certification\IncidentResponseService;
use Illuminate\Console\Command;
use Throwable;

class ComplianceIncidentCommand extends Command
{
    protected $signature = 'compliance:incident
        {action : Action to perform (list, create, resolve, postmortem, stats)}
        {--id= : Incident ID for resolve/postmortem actions}
        {--severity= : Severity level for create (critical, high, medium, low)}
        {--title= : Incident title for create}
        {--description= : Incident description for create}
        {--resolution= : Resolution text for resolve action}';

    protected $description = 'Manage security incidents for SOC 2 compliance';

    public function handle(IncidentResponseService $service): int
    {
        $action = (string) $this->argument('action');

        try {
            return match ($action) {
                'list'       => $this->listIncidents($service),
                'create'     => $this->createIncident($service),
                'resolve'    => $this->resolveIncident($service),
                'postmortem' => $this->showPostmortem($service),
                'stats'      => $this->showStatistics($service),
                default      => $this->invalidAction($action),
            };
        } catch (Throwable $e) {
            $this->error("Incident command failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    private function listIncidents(IncidentResponseService $service): int
    {
        $incidents = $service->getOpenIncidents();

        if ($incidents->isEmpty()) {
            $this->info('No open incidents.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Title', 'Severity', 'Status', 'Detected At'],
            $incidents->map(fn ($i) => [
                substr((string) $i->id, 0, 8) . '...',
                $i->title,
                $i->severity,
                $i->status,
                $i->detected_at?->format('Y-m-d H:i') ?? 'N/A',
            ])->toArray()
        );

        return self::SUCCESS;
    }

    private function createIncident(IncidentResponseService $service): int
    {
        $title = $this->option('title');
        $severity = $this->option('severity');

        if (! $title || ! $severity) {
            $this->error('--title and --severity are required for create action.');

            return self::FAILURE;
        }

        $incident = $service->createIncident([
            'title'       => $title,
            'description' => $this->option('description') ?? $title,
            'severity'    => $severity,
        ]);

        $this->info("Incident created: {$incident->id}");

        return self::SUCCESS;
    }

    private function resolveIncident(IncidentResponseService $service): int
    {
        $id = $this->option('id');
        $resolution = $this->option('resolution');

        if (! $id || ! $resolution) {
            $this->error('--id and --resolution are required for resolve action.');

            return self::FAILURE;
        }

        $service->resolveIncident($id, $resolution);
        $this->info("Incident {$id} resolved.");

        return self::SUCCESS;
    }

    private function showPostmortem(IncidentResponseService $service): int
    {
        $id = $this->option('id');

        if (! $id) {
            $this->error('--id is required for postmortem action.');

            return self::FAILURE;
        }

        $postmortem = $service->generatePostmortem($id);
        $this->line(json_encode($postmortem, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function showStatistics(IncidentResponseService $service): int
    {
        $stats = $service->getIncidentStatistics();
        $this->line(json_encode($stats, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function invalidAction(string $action): int
    {
        $this->error("Invalid action: {$action}. Use: list, create, resolve, postmortem, stats");

        return self::FAILURE;
    }
}
