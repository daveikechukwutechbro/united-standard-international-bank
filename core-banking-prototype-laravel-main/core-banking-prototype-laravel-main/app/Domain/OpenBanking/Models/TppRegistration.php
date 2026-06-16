<?php

declare(strict_types=1);

namespace App\Domain\OpenBanking\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string      $id
 * @property string      $tpp_id
 * @property string      $name
 * @property string      $client_id
 * @property string      $client_secret_hash
 * @property string|null $eidas_certificate
 * @property array<int, string> $redirect_uris
 * @property array<int, string> $roles
 * @property string      $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class TppRegistration extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;
    use HasUuids;

    protected $table = 'tpp_registrations';

    protected $fillable = [
        'tpp_id',
        'name',
        'client_id',
        'client_secret_hash',
        'eidas_certificate',
        'redirect_uris',
        'roles',
        'status',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'redirect_uris' => 'array',
            'roles'         => 'array',
        ];
    }

    /** @return HasMany<Consent, $this> */
    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class, 'tpp_id', 'tpp_id');
    }
}
