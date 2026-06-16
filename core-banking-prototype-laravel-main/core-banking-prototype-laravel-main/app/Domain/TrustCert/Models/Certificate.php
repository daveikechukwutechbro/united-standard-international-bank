<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\Models;

use App\Domain\TrustCert\Enums\CertificateStatus;
use App\Domain\TrustCert\Enums\IssuerType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'subject',
        'issuer_type',
        'status',
        'credential_type',
        'claims',
        'issued_at',
        'expires_at',
        'revoked_at',
        'revocation_reason',
        'metadata',
    ];

    protected $casts = [
        'issuer_type' => IssuerType::class,
        'status'      => CertificateStatus::class,
        'claims'      => 'array',
        'metadata'    => 'array',
        'issued_at'   => 'datetime',
        'expires_at'  => 'datetime',
        'revoked_at'  => 'datetime',
    ];

    /** @return BelongsTo<\App\Models\User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
