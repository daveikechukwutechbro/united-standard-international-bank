<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Models;

use App\Domain\Shared\Traits\UsesTenantConnection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Multi-signature wallet signer model.
 *
 * @property string $id
 * @property string $multi_sig_wallet_id
 * @property int|null $user_id
 * @property string|null $hardware_wallet_association_id
 * @property string $signer_type
 * @property string $public_key
 * @property string|null $address
 * @property string|null $label
 * @property int $signer_order
 * @property bool $is_active
 * @property array<string, mixed>|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read MultiSigWallet $wallet
 * @property-read User|null $user
 * @property-read HardwareWalletAssociation|null $hardwareWalletAssociation
 * @property-read Collection<int, MultiSigSignerApproval> $approvals
 */
class MultiSigWalletSigner extends Model
{
    use HasUuids;
    use SoftDeletes;
    use UsesTenantConnection;

    public const TYPE_HARDWARE_LEDGER = 'hardware_ledger';

    public const TYPE_HARDWARE_TREZOR = 'hardware_trezor';

    public const TYPE_INTERNAL = 'internal';

    public const TYPE_EXTERNAL = 'external';

    protected $table = 'multi_sig_wallet_signers';

    protected $fillable = [
        'multi_sig_wallet_id',
        'user_id',
        'hardware_wallet_association_id',
        'signer_type',
        'public_key',
        'address',
        'label',
        'signer_order',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'metadata'     => 'array',
        'is_active'    => 'boolean',
        'signer_order' => 'integer',
    ];

    /**
     * Get the multi-sig wallet this signer belongs to.
     *
     * @return BelongsTo<MultiSigWallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(MultiSigWallet::class, 'multi_sig_wallet_id');
    }

    /**
     * Get the user associated with this signer (if any).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the hardware wallet association (if any).
     *
     * @return BelongsTo<HardwareWalletAssociation, $this>
     */
    public function hardwareWalletAssociation(): BelongsTo
    {
        return $this->belongsTo(HardwareWalletAssociation::class);
    }

    /**
     * Get the approvals made by this signer.
     *
     * @return HasMany<MultiSigSignerApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(MultiSigSignerApproval::class, 'signer_id');
    }

    /**
     * Scope to get active signers.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get signers by type.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('signer_type', $type);
    }

    /**
     * Scope to get hardware wallet signers.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeHardwareWallet(Builder $query): Builder
    {
        return $query->whereIn('signer_type', [self::TYPE_HARDWARE_LEDGER, self::TYPE_HARDWARE_TREZOR]);
    }

    /**
     * Check if this is a hardware wallet signer.
     */
    public function isHardwareWallet(): bool
    {
        return in_array($this->signer_type, [self::TYPE_HARDWARE_LEDGER, self::TYPE_HARDWARE_TREZOR], true);
    }

    /**
     * Check if this is a Ledger signer.
     */
    public function isLedger(): bool
    {
        return $this->signer_type === self::TYPE_HARDWARE_LEDGER;
    }

    /**
     * Check if this is a Trezor signer.
     */
    public function isTrezor(): bool
    {
        return $this->signer_type === self::TYPE_HARDWARE_TREZOR;
    }

    /**
     * Deactivate this signer.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Reactivate this signer.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Get the display name for this signer.
     */
    public function getDisplayName(): string
    {
        if ($this->label) {
            return $this->label;
        }

        if ($this->user) {
            return $this->user->name;
        }

        return "Signer #{$this->signer_order}";
    }
}
