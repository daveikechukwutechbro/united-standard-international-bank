<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Models;

use App\Domain\Shared\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * GDPR Article 33/34 — Data Breach with notification deadline tracking.
 *
 * @property Carbon $discovery_time
 * @property Carbon $notification_deadline
 * @property Carbon|null $authority_notified_at
 * @property Carbon|null $subjects_notified_at
 */
class DataBreach extends Model
{
    use UsesTenantConnection;
    use HasFactory;
    use HasUuids;

    protected $table = 'data_breaches';

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'discovery_time',
        'notification_deadline',
        'severity',
        'status',
        'affected_data_types',
        'affected_individuals_count',
        'measures_taken',
        'authority_notified_at',
        'subjects_notified_at',
        'reported_by',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discovery_time'             => 'datetime',
            'notification_deadline'      => 'datetime',
            'affected_data_types'        => 'array',
            'affected_individuals_count' => 'integer',
            'measures_taken'             => 'array',
            'authority_notified_at'      => 'datetime',
            'subjects_notified_at'       => 'datetime',
            'metadata'                   => 'array',
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['resolved', 'closed']);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeOverdue($query)
    {
        return $query->where('notification_deadline', '<', now())
            ->whereNull('authority_notified_at');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeApproachingDeadline($query, int $hoursThreshold = 12)
    {
        return $query->whereBetween('notification_deadline', [now(), now()->addHours($hoursThreshold)])
            ->whereNull('authority_notified_at');
    }

    public function isOverdue(): bool
    {
        return $this->notification_deadline->isPast() && $this->authority_notified_at === null;
    }

    public function hoursUntilDeadline(): float
    {
        return now()->diffInHours($this->notification_deadline, false);
    }

    public function isAuthorityNotified(): bool
    {
        return $this->authority_notified_at !== null;
    }

    public function areSubjectsNotified(): bool
    {
        return $this->subjects_notified_at !== null;
    }
}
