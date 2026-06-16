<?php

declare(strict_types=1);

namespace App\Domain\Monitoring\Models;

use Illuminate\Database\Eloquent\Model;

class EventMigration extends Model
{
    protected $fillable = [
        'domain',
        'source_table',
        'target_table',
        'batch_size',
        'events_migrated',
        'events_total',
        'status',
        'started_at',
        'completed_at',
        'error_message',
        'verification_result',
    ];

    protected function casts(): array
    {
        return [
            'events_migrated'     => 'integer',
            'events_total'        => 'integer',
            'batch_size'          => 'integer',
            'started_at'          => 'datetime',
            'completed_at'        => 'datetime',
            'verification_result' => 'array',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getProgressPercentage(): float
    {
        if ($this->events_total === 0) {
            return 100.0;
        }

        return round(($this->events_migrated / $this->events_total) * 100, 2);
    }
}
