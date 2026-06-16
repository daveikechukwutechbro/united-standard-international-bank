<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Models;

use App\Domain\Ledger\Enums\ReconciliationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property string $domain
 * @property string $gl_balance
 * @property string $domain_balance
 * @property string $variance
 * @property ReconciliationStatus $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static ReconciliationReport|null find(mixed $id, array<int, string> $columns = ['*'])
 * @method static ReconciliationReport create(array<string, mixed> $attributes = [])
 */
class ReconciliationReport extends Model
{
    use HasUuids;

    protected $table = 'reconciliation_reports';

    /** @var list<string> */
    protected $guarded = [];

    /**
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'status'       => ReconciliationStatus::class,
    ];
}
