<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Services\Certification;

use App\Domain\Compliance\Models\SecurityIncident;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * SOC 2 Incident Response Service.
 *
 * Manages the full lifecycle of security incidents: creation, investigation,
 * resolution, postmortem generation, and statistical reporting for SOC 2
 * Trust Services Criteria (CC7.2, CC7.3, CC7.4, CC7.5).
 */
class IncidentResponseService
{
    /**
     * Create a new security incident.
     *
     * @param array<string, mixed> $data Incident data (title, description, severity, affected_systems, reported_by, assigned_to, metadata)
     */
    public function createIncident(array $data): SecurityIncident
    {
        $incident = SecurityIncident::create([
            'title'       => $data['title'],
            'description' => $data['description'],
            'severity'    => $data['severity'] ?? 'medium',
            'status'      => 'open',
            'timeline'    => [
                [
                    'timestamp' => now()->toIso8601String(),
                    'action'    => 'incident_created',
                    'actor'     => $data['reported_by'] ?? auth()->user()?->name ?? 'system',
                    'notes'     => 'Incident reported and opened for investigation.',
                ],
            ],
            'resolution'       => null,
            'affected_systems' => $data['affected_systems'] ?? [],
            'reported_by'      => $data['reported_by'] ?? auth()->user()?->name ?? 'system',
            'assigned_to'      => $data['assigned_to'] ?? null,
            'detected_at'      => $data['detected_at'] ?? now(),
            'resolved_at'      => null,
            'postmortem'       => null,
            'metadata'         => $data['metadata'] ?? [],
        ]);

        Log::info('Security incident created', [
            'id'          => $incident->id,
            'title'       => $incident->title,
            'severity'    => $incident->severity,
            'reported_by' => $incident->reported_by,
        ]);

        return $incident;
    }

    /**
     * Update an existing security incident and append a timeline entry.
     *
     * @param string               $id   Incident UUID
     * @param array<string, mixed> $data Fields to update
     */
    public function updateIncident(string $id, array $data): SecurityIncident
    {
        $incident = SecurityIncident::findOrFail($id);

        $updatableFields = [
            'title', 'description', 'severity', 'status',
            'affected_systems', 'assigned_to', 'metadata',
        ];

        $updateData = array_intersect_key($data, array_flip($updatableFields));
        $incident->update($updateData);

        $changedFields = array_keys($updateData);
        $incident->addTimelineEntry(
            'incident_updated',
            auth()->user()?->name ?? 'system',
            'Updated fields: ' . implode(', ', $changedFields)
        );

        Log::info('Security incident updated', [
            'id'             => $incident->id,
            'updated_fields' => $changedFields,
        ]);

        return $incident->fresh();
    }

    /**
     * Resolve a security incident.
     *
     * @param string $id         Incident UUID
     * @param string $resolution Description of the resolution
     */
    public function resolveIncident(string $id, string $resolution): SecurityIncident
    {
        $incident = SecurityIncident::findOrFail($id);

        $incident->update([
            'status'      => 'resolved',
            'resolution'  => $resolution,
            'resolved_at' => now(),
        ]);

        $incident->addTimelineEntry(
            'incident_resolved',
            auth()->user()?->name ?? 'system',
            $resolution
        );

        Log::info('Security incident resolved', [
            'id'                    => $incident->id,
            'title'                 => $incident->title,
            'resolution_time_hours' => $incident->detected_at
                ? $incident->detected_at->diffInHours(now())
                : null,
        ]);

        return $incident->fresh();
    }

    /**
     * Retrieve incidents with optional status and severity filters.
     *
     * @param string|null $status   Filter by status (open, investigating, mitigating, resolved, closed)
     * @param string|null $severity Filter by severity (low, medium, high, critical)
     *
     * @return Collection<int, SecurityIncident>
     */
    public function getIncidents(?string $status = null, ?string $severity = null): Collection
    {
        $query = SecurityIncident::query();

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($severity !== null) {
            $query->forSeverity($severity);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get all currently open incidents (open, investigating, or mitigating).
     *
     * @return Collection<int, SecurityIncident>
     */
    public function getOpenIncidents(): Collection
    {
        return SecurityIncident::open()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Generate a postmortem report for a resolved incident.
     *
     * @param string $id Incident UUID
     *
     * @return array<string, mixed>
     */
    public function generatePostmortem(string $id): array
    {
        $incident = SecurityIncident::findOrFail($id);

        $resolutionTimeHours = null;
        if ($incident->detected_at && $incident->resolved_at) {
            $resolutionTimeHours = round($incident->detected_at->diffInHours($incident->resolved_at), 2);
        }

        $postmortem = [
            'incident_id'      => $incident->id,
            'title'            => $incident->title,
            'severity'         => $incident->severity,
            'status'           => $incident->status,
            'generated_at'     => now()->toIso8601String(),
            'incident_summary' => [
                'description'           => $incident->description,
                'detected_at'           => $incident->detected_at?->toIso8601String(),
                'resolved_at'           => $incident->resolved_at?->toIso8601String(),
                'resolution_time_hours' => $resolutionTimeHours,
                'reported_by'           => $incident->reported_by,
                'assigned_to'           => $incident->assigned_to,
            ],
            'affected_systems' => $incident->affected_systems ?? [],
            'timeline'         => $incident->timeline ?? [],
            'resolution'       => $incident->resolution,
            'root_cause'       => $incident->postmortem['root_cause'] ?? 'Root cause analysis pending.',
            'lessons_learned'  => $incident->postmortem['lessons_learned'] ?? [
                'Detection: Review monitoring thresholds and alerting rules.',
                'Response: Evaluate incident response procedures for improvement.',
                'Prevention: Identify and implement measures to prevent recurrence.',
            ],
            'action_items'      => $incident->postmortem['action_items'] ?? [],
            'impact_assessment' => [
                'affected_system_count' => count($incident->affected_systems ?? []),
                'severity'              => $incident->severity,
                'was_data_compromised'  => $incident->metadata['data_compromised'] ?? false,
                'customer_impact'       => $incident->metadata['customer_impact'] ?? 'Assessment pending.',
            ],
        ];

        // Persist the postmortem on the incident record
        $incident->update(['postmortem' => $postmortem]);

        Log::info('Postmortem generated for security incident', [
            'id'    => $incident->id,
            'title' => $incident->title,
        ]);

        return $postmortem;
    }

    /**
     * Get incident statistics: counts by severity, status, and average resolution time.
     *
     * @return array<string, mixed>
     */
    public function getIncidentStatistics(): array
    {
        $incidents = SecurityIncident::all();

        $bySeverity = $incidents->groupBy('severity')->map->count()->toArray();
        $byStatus = $incidents->groupBy('status')->map->count()->toArray();

        $resolvedIncidents = $incidents->filter(function (SecurityIncident $incident) {
            return $incident->detected_at !== null && $incident->resolved_at !== null;
        });

        $avgResolutionHours = null;
        if ($resolvedIncidents->isNotEmpty()) {
            $totalHours = $resolvedIncidents->sum(function (SecurityIncident $incident) {
                return $incident->detected_at->diffInHours($incident->resolved_at);
            });
            $avgResolutionHours = round($totalHours / $resolvedIncidents->count(), 2);
        }

        return [
            'generated_at'                  => now()->toIso8601String(),
            'total_incidents'               => $incidents->count(),
            'open_incidents'                => $incidents->filter(fn (SecurityIncident $i) => ! $i->isResolved())->count(),
            'resolved_incidents'            => $resolvedIncidents->count(),
            'by_severity'                   => $bySeverity,
            'by_status'                     => $byStatus,
            'average_resolution_time_hours' => $avgResolutionHours,
            'critical_open'                 => $incidents
                ->where('severity', 'critical')
                ->filter(fn (SecurityIncident $i) => ! $i->isResolved())
                ->count(),
            'mttr_by_severity' => $this->calculateMttrBySeverity($incidents),
        ];
    }

    /**
     * Calculate Mean Time To Resolve (MTTR) grouped by severity.
     *
     * @param Collection<int, SecurityIncident> $incidents
     *
     * @return array<string, float|null>
     */
    private function calculateMttrBySeverity(Collection $incidents): array
    {
        $severities = ['low', 'medium', 'high', 'critical'];
        $mttr = [];

        foreach ($severities as $severity) {
            $resolved = $incidents->where('severity', $severity)->filter(function (SecurityIncident $incident) {
                return $incident->detected_at !== null && $incident->resolved_at !== null;
            });

            if ($resolved->isNotEmpty()) {
                $totalHours = $resolved->sum(function (SecurityIncident $incident) {
                    return $incident->detected_at->diffInHours($incident->resolved_at);
                });
                $mttr[$severity] = round($totalHours / $resolved->count(), 2);
            } else {
                $mttr[$severity] = null;
            }
        }

        return $mttr;
    }
}
