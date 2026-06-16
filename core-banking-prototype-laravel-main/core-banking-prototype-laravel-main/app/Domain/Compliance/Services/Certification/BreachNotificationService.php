<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Services\Certification;

use App\Domain\Compliance\Models\DataBreach;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

/**
 * GDPR Article 33/34 — Breach notification with 72-hour deadline.
 */
class BreachNotificationService
{
    /**
     * Report a new data breach.
     *
     * @param  array<string, mixed>  $data
     */
    public function reportBreach(array $data): DataBreach
    {
        $discoveryTime = now();
        $deadlineHours = Config::get('compliance-certification.gdpr.breach_notification_deadline_hours', 72);

        return DataBreach::create(array_merge($data, [
            'discovery_time'        => $discoveryTime,
            'notification_deadline' => $discoveryTime->copy()->addHours($deadlineHours),
            'status'                => 'detected',
        ]));
    }

    /**
     * Get all breaches with optional status filter.
     *
     * @return Collection<int, DataBreach>
     */
    public function getBreaches(?string $status = null): Collection
    {
        $query = DataBreach::query();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('discovery_time')->get();
    }

    /**
     * Record authority notification for a breach.
     */
    public function notifyAuthority(string $id, ?string $notes = null): DataBreach
    {
        $breach = DataBreach::findOrFail($id);
        $breach->update([
            'authority_notified_at' => now(),
            'status'                => 'authority_notified',
            'metadata'              => array_merge($breach->metadata ?? [], [
                'authority_notification_notes' => $notes,
            ]),
        ]);

        return $breach->refresh();
    }

    /**
     * Record data subject notification for a breach.
     */
    public function notifySubjects(string $id, ?string $notes = null): DataBreach
    {
        $breach = DataBreach::findOrFail($id);
        $breach->update([
            'subjects_notified_at' => now(),
            'status'               => 'subjects_notified',
            'metadata'             => array_merge($breach->metadata ?? [], [
                'subjects_notification_notes' => $notes,
            ]),
        ]);

        return $breach->refresh();
    }

    /**
     * Resolve a breach.
     *
     * @param  array<string, mixed>  $resolution
     */
    public function resolveBreach(string $id, array $resolution): DataBreach
    {
        $breach = DataBreach::findOrFail($id);
        $breach->update([
            'status'         => 'resolved',
            'measures_taken' => array_merge($breach->measures_taken ?? [], $resolution['measures'] ?? []),
            'metadata'       => array_merge($breach->metadata ?? [], [
                'resolution_summary' => $resolution['summary'] ?? null,
                'resolved_at'        => now()->toIso8601String(),
            ]),
        ]);

        return $breach->refresh();
    }

    /**
     * Check for approaching deadlines.
     *
     * @return array<string, mixed>
     */
    public function checkDeadlines(): array
    {
        $overdue = DataBreach::overdue()->get();
        $approaching = DataBreach::approachingDeadline(12)->get();

        return [
            'overdue_count'    => $overdue->count(),
            'overdue_breaches' => $overdue->map(fn (DataBreach $b) => [
                'id'            => $b->id,
                'title'         => $b->title,
                'severity'      => $b->severity,
                'deadline'      => $b->notification_deadline->toIso8601String(),
                'hours_overdue' => abs($b->hoursUntilDeadline()),
            ])->toArray(),
            'approaching_count' => $approaching->count(),
            'approaching'       => $approaching->map(fn (DataBreach $b) => [
                'id'              => $b->id,
                'title'           => $b->title,
                'severity'        => $b->severity,
                'hours_remaining' => $b->hoursUntilDeadline(),
            ])->toArray(),
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get breach summary statistics.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        $breaches = DataBreach::all();

        return [
            'total'                      => $breaches->count(),
            'open'                       => $breaches->whereNotIn('status', ['resolved', 'closed'])->count(),
            'by_severity'                => $breaches->groupBy('severity')->map->count()->toArray(),
            'by_status'                  => $breaches->groupBy('status')->map->count()->toArray(),
            'overdue_notifications'      => DataBreach::overdue()->count(),
            'total_affected_individuals' => $breaches->sum('affected_individuals_count'),
        ];
    }

    /**
     * Get demo breach data.
     *
     * @return array<string, mixed>
     */
    public function getDemoSummary(): array
    {
        return [
            'total'                      => 2,
            'open'                       => 0,
            'by_severity'                => ['medium' => 1, 'low' => 1],
            'by_status'                  => ['resolved' => 2],
            'overdue_notifications'      => 0,
            'total_affected_individuals' => 150,
            'deadline_check'             => [
                'overdue_count'     => 0,
                'approaching_count' => 0,
                'checked_at'        => now()->toIso8601String(),
            ],
        ];
    }
}
