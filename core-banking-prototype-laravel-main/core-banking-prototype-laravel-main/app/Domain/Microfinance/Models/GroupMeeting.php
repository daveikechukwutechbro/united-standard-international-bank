<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $group_id
 * @property \Illuminate\Support\Carbon $meeting_date
 * @property int $attendees_count
 * @property int $total_members
 * @property string|null $minutes
 * @property \Illuminate\Support\Carbon|null $next_meeting_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder<static> where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static static|null find(mixed $id)
 * @method static static|null first()
 * @method static \Illuminate\Database\Eloquent\Collection<int, static> get()
 * @method static int count()
 */
class GroupMeeting extends Model
{
    use HasUuids;

    protected $table = 'mfi_group_meetings';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'group_id',
        'meeting_date',
        'attendees_count',
        'total_members',
        'minutes',
        'next_meeting_date',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'meeting_date'      => 'date',
            'next_meeting_date' => 'date',
            'attendees_count'   => 'integer',
            'total_members'     => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}
