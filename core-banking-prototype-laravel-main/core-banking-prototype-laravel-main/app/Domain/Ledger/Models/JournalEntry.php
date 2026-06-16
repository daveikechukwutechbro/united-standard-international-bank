<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Models;

use App\Domain\Ledger\Enums\EntryStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $entry_number
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $posted_at
 * @property EntryStatus $status
 * @property string|null $source_domain
 * @property string|null $source_event_id
 * @property string|null $reversed_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static JournalEntry|null find(mixed $id, array<int, string> $columns = ['*'])
 * @method static JournalEntry|null first(array<int, string> $columns = ['*'])
 * @method static JournalEntry firstOrFail(array<int, string> $columns = ['*'])
 * @method static JournalEntry create(array<string, mixed> $attributes = [])
 */
class JournalEntry extends Model
{
    use HasUuids;

    protected $table = 'journal_entries';

    /** @var list<string> */
    protected $guarded = [];

    /**
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'status'    => EntryStatus::class,
        'posted_at' => 'datetime',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => EntryStatus::DRAFT,
    ];

    /**
     * Journal lines for this entry.
     *
     * @return HasMany<JournalLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'journal_entry_id', 'id');
    }

    /**
     * Returns true when the sum of debits equals the sum of credits across all lines.
     */
    public function isBalanced(): bool
    {
        $lines = $this->lines()->get();

        $totalDebit = $lines->sum(fn (JournalLine $line): float => (float) $line->debit_amount);
        $totalCredit = $lines->sum(fn (JournalLine $line): float => (float) $line->credit_amount);

        return abs($totalDebit - $totalCredit) < 0.00001;
    }
}
