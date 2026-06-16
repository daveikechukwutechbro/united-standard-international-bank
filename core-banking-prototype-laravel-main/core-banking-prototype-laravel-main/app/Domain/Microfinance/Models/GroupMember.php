<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Models;

use App\Domain\Microfinance\Enums\MemberRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $group_id
 * @property int $user_id
 * @property MemberRole $role
 * @property \Illuminate\Support\Carbon $joined_at
 * @property \Illuminate\Support\Carbon|null $left_at
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder<static> where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static static|null find(mixed $id)
 * @method static static|null first()
 * @method static \Illuminate\Database\Eloquent\Collection<int, static> get()
 * @method static int count()
 */
class GroupMember extends Model
{
    use HasUuids;

    protected $table = 'mfi_group_members';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'group_id',
        'user_id',
        'role',
        'joined_at',
        'left_at',
        'is_active',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'role'      => MemberRole::class,
            'joined_at' => 'date',
            'left_at'   => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
