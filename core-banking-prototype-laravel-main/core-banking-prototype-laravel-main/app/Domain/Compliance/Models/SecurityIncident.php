<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Models;

use App\Domain\Shared\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityIncident extends Model
{
    use UsesTenantConnection;
    use HasFactory;
    use HasUuids;

    protected $table = 'security_incidents';

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'severity',
        'status',
        'timeline',
        'resolution',
        'affected_systems',
        'reported_by',
        'assigned_to',
        'detected_at',
        'resolved_at',
        'postmortem',
        'metadata',
    ];

    protected $casts = [
        'timeline'         => 'array',
        'affected_systems' => 'array',
        'postmortem'       => 'array',
        'metadata'         => 'array',
        'detected_at'      => 'datetime',
        'resolved_at'      => 'datetime',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'investigating', 'mitigating']);
    }

    public function scopeForSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function isResolved(): bool
    {
        return in_array($this->status, ['resolved', 'closed']);
    }

    public function addTimelineEntry(string $action, ?string $actor = null, ?string $notes = null): void
    {
        $timeline = $this->timeline ?? [];
        $timeline[] = [
            'timestamp' => now()->toIso8601String(),
            'action'    => $action,
            'actor'     => $actor,
            'notes'     => $notes,
        ];
        $this->update(['timeline' => $timeline]);
    }
}
