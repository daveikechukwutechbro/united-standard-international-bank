<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $journal_entry_id
 * @property string $account_code
 * @property string $debit_amount
 * @property string $credit_amount
 * @property string $currency
 * @property string|null $narrative
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static JournalLine|null find(mixed $id, array<int, string> $columns = ['*'])
 * @method static JournalLine create(array<string, mixed> $attributes = [])
 */
class JournalLine extends Model
{
    protected $table = 'journal_lines';

    /** @var list<string> */
    protected $guarded = [];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'debit_amount'  => '0.0000',
        'credit_amount' => '0.0000',
        'currency'      => 'USD',
    ];

    /**
     * Parent journal entry.
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id', 'id');
    }

    /**
     * Ledger account associated with this line.
     *
     * @return BelongsTo<LedgerAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'account_code', 'code');
    }
}
