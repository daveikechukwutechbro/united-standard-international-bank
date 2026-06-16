<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Models;

use App\Domain\Microfinance\Enums\GroupStatus;
use App\Domain\Microfinance\Enums\MeetingFrequency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $name
 * @property string|null $center_name
 * @property string|null $office_name
 * @property GroupStatus $status
 * @property MeetingFrequency $meeting_frequency
 * @property string|null $meeting_day
 * @property \Illuminate\Support\Carbon|null $activation_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @method static Builder<static> active()
 * @method static Builder<static> where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static Builder<static> whereIn(string $column, mixed $values)
 * @method static static|null find(mixed $id)
 * @method static static|null first()
 * @method static \Illuminate\Database\Eloquent\Collection<int, static> get()
 * @method static int count()
 * @method static Builder<static> orderBy(string $column, string $direction = 'asc')
 */
class Group extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'mfi_groups';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'center_name',
        'office_name',
        'status',
        'meeting_frequency',
        'meeting_day',
        'activation_date',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status'            => GroupStatus::class,
            'meeting_frequency' => MeetingFrequency::class,
            'activation_date'   => 'date',
        ];
    }

    /**
     * @return HasMany<GroupMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'group_id');
    }

    /**
     * @return HasMany<GroupMeeting, $this>
     */
    public function meetings(): HasMany
    {
        return $this->hasMany(GroupMeeting::class, 'group_id');
    }

    /**
     * @param  Builder<static> $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', GroupStatus::ACTIVE->value);
    }
}
