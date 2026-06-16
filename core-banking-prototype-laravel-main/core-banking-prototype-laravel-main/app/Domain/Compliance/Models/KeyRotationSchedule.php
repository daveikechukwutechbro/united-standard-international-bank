<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Models;

use App\Domain\Shared\Traits\UsesTenantConnection;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeyRotationSchedule extends Model
{
    use UsesTenantConnection;
    use HasFactory;
    use HasUuids;

    protected $table = 'key_rotation_schedules';

    protected $fillable = [
        'tenant_id',
        'key_type',
        'key_identifier',
        'rotation_interval_days',
        'last_rotated_at',
        'next_rotation_at',
        'status',
        'algorithm',
        'rotated_by',
        'rotation_history',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_rotated_at'        => 'datetime',
            'next_rotation_at'       => 'datetime',
            'rotation_interval_days' => 'integer',
            'rotation_history'       => 'array',
            'metadata'               => 'array',
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeOverdue($query)
    {
        return $query->where('next_rotation_at', '<', Carbon::now())
            ->where('status', 'active');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeForType($query, string $keyType)
    {
        return $query->where('key_type', $keyType);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeDueSoon($query, int $days = 14)
    {
        return $query->where('next_rotation_at', '<=', Carbon::now()->addDays($days))
            ->where('next_rotation_at', '>', Carbon::now())
            ->where('status', 'active');
    }

    public function isOverdue(): bool
    {
        if (! $this->next_rotation_at) {
            return false;
        }

        return $this->next_rotation_at->isPast();
    }

    public function recordRotation(?string $rotatedBy = null): void
    {
        $history = $this->rotation_history ?? [];
        $history[] = [
            'rotated_at'             => Carbon::now()->toIso8601String(),
            'rotated_by'             => $rotatedBy ?? 'system',
            'previous_next_rotation' => $this->next_rotation_at?->toIso8601String(),
        ];

        $this->update([
            'last_rotated_at'  => Carbon::now(),
            'next_rotation_at' => Carbon::now()->addDays($this->rotation_interval_days),
            'status'           => 'active',
            'rotated_by'       => $rotatedBy ?? 'system',
            'rotation_history' => $history,
        ]);
    }
}
