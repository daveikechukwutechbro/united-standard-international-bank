<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Models;

use App\Domain\Microfinance\Enums\ProvisionCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $loan_id
 * @property ProvisionCategory $category
 * @property string $provision_amount
 * @property int $days_overdue
 * @property \Illuminate\Support\Carbon $review_date
 * @property int|null $reviewed_by
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder<static> where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static static|null find(mixed $id)
 * @method static static|null first()
 * @method static \Illuminate\Database\Eloquent\Collection<int, static> get()
 * @method static int count()
 */
class LoanProvision extends Model
{
    use HasUuids;

    protected $table = 'mfi_loan_provisions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'loan_id',
        'category',
        'provision_amount',
        'days_overdue',
        'review_date',
        'reviewed_by',
        'notes',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'category'         => ProvisionCategory::class,
            'provision_amount' => 'decimal:2',
            'days_overdue'     => 'integer',
            'review_date'      => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
