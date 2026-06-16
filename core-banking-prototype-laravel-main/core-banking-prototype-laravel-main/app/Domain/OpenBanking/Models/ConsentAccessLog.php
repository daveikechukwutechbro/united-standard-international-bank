<?php

declare(strict_types=1);

namespace App\Domain\OpenBanking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property string      $consent_id
 * @property string      $tpp_id
 * @property string      $endpoint
 * @property string|null $ip_address
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ConsentAccessLog extends Model
{
    protected $table = 'consent_access_logs';

    protected $fillable = [
        'consent_id',
        'tpp_id',
        'endpoint',
        'ip_address',
    ];

    /** @return BelongsTo<Consent, $this> */
    public function consent(): BelongsTo
    {
        return $this->belongsTo(Consent::class);
    }
}
