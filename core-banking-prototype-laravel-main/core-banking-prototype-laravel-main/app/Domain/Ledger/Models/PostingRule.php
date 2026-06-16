<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string $trigger_event
 * @property string $debit_account
 * @property string $credit_account
 * @property string $amount_expression
 * @property bool $is_active
 * @property int $priority
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder<PostingRule> active()
 * @method static Builder<PostingRule> forEvent(string $event)
 * @method static PostingRule|null find(mixed $id, array<int, string> $columns = ['*'])
 * @method static PostingRule create(array<string, mixed> $attributes = [])
 */
class PostingRule extends Model
{
    use HasUuids;

    protected $table = 'posting_rules';

    /** @var list<string> */
    protected $guarded = [];

    /**
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'priority'  => 'integer',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'priority'  => 0,
    ];

    /**
     * Scope: only active posting rules.
     *
     * @param  Builder<PostingRule> $query
     * @return Builder<PostingRule>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: rules matching a specific trigger event.
     *
     * @param  Builder<PostingRule> $query
     * @return Builder<PostingRule>
     */
    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('trigger_event', $event);
    }
}
