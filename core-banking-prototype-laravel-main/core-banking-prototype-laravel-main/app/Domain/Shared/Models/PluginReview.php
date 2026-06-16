<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginReview extends Model
{
    use HasUuids;

    protected $fillable = [
        'plugin_id',
        'reviewer_id',
        'status',
        'security_score',
        'notes',
        'scan_results',
        'reviewed_at',
    ];

    protected $casts = [
        'scan_results'   => 'array',
        'security_score' => 'integer',
        'reviewed_at'    => 'datetime',
    ];

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
