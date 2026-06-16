<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Tenant model for multi-tenancy support.
 *
 * Links to the existing Jetstream Team model to leverage
 * existing team-based organization structure.
 *
 * @property string $id UUID identifier
 * @property int|null $team_id Link to existing Team
 * @property string $name Tenant display name
 * @property string|null $plan Subscription plan
 * @property \Carbon\Carbon|null $trial_ends_at Trial expiration
 * @property array<string, mixed> $data Additional tenant data (JSON)
 * @property \Carbon\Carbon|null $deleted_at Soft-delete timestamp
 * @property \Carbon\Carbon|null $deletion_scheduled_at Scheduled deletion date (14-day grace)
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;
    use SoftDeletes;

    /**
     * Get the custom columns for the tenant model.
     *
     * @return array<int, string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'team_id',
            'name',
            'plan',
            'trial_ends_at',
            'deleted_at',
            'deletion_scheduled_at',
        ];
    }

    /**
     * Get the team associated with this tenant.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Create a tenant from an existing team.
     */
    public static function createFromTeam(Team $team): self
    {
        return static::create([
            'team_id' => $team->id,
            'name'    => $team->name,
            'plan'    => 'default',
        ]);
    }
}
