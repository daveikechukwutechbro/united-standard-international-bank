<?php

declare(strict_types=1);

namespace App\Domain\Banking\Services;

use App\Domain\Banking\Models\SepaMandate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Manages SEPA mandate lifecycle: creation, suspension, cancellation,
 * reactivation, and automatic expiry of stale mandates.
 */
class SepaMandateService
{
    /**
     * Create a new SEPA mandate.
     *
     * @param  array<string, mixed>  $data
     */
    public function createMandate(int $userId, array $data): SepaMandate
    {
        $mandateId = 'MNDT-' . strtoupper(substr(Str::uuid()->toString(), 0, 8));

        /** @var SepaMandate $mandate */
        $mandate = SepaMandate::create([
            'user_id'       => $userId,
            'mandate_id'    => $mandateId,
            'creditor_id'   => $data['creditor_id'],
            'creditor_name' => $data['creditor_name'],
            'creditor_iban' => $data['creditor_iban'],
            'debtor_name'   => $data['debtor_name'],
            'debtor_iban'   => $data['debtor_iban'],
            'scheme'        => $data['scheme'] ?? 'CORE',
            'status'        => 'active',
            'signed_at'     => $data['signed_at'] ?? now(),
            'max_amount'    => $data['max_amount'] ?? null,
            'frequency'     => $data['frequency'] ?? null,
        ]);

        return $mandate;
    }

    /**
     * Suspend a mandate (temporarily halt collections).
     */
    public function suspendMandate(string $mandateId): SepaMandate
    {
        $mandate = $this->findByMandateIdOrFail($mandateId);
        $mandate->update(['status' => 'suspended']);

        return $mandate->fresh() ?? $mandate;
    }

    /**
     * Cancel a mandate permanently.
     */
    public function cancelMandate(string $mandateId): SepaMandate
    {
        $mandate = $this->findByMandateIdOrFail($mandateId);
        $mandate->update(['status' => 'cancelled']);

        return $mandate->fresh() ?? $mandate;
    }

    /**
     * Reactivate a previously suspended mandate.
     */
    public function reactivateMandate(string $mandateId): SepaMandate
    {
        $mandate = $this->findByMandateIdOrFail($mandateId);
        $mandate->update(['status' => 'active']);

        return $mandate->fresh() ?? $mandate;
    }

    /**
     * Get all active mandates for a user.
     *
     * @return Collection<int, SepaMandate>
     */
    public function getMandatesForUser(int $userId): Collection
    {
        return SepaMandate::active()->forUser($userId)->get();
    }

    /**
     * Find a mandate by its SEPA mandate reference ID.
     */
    public function findByMandateId(string $mandateId): ?SepaMandate
    {
        return SepaMandate::where('mandate_id', $mandateId)->first();
    }

    /**
     * Expire mandates that have had no collection activity in 36 months.
     * Returns the number of mandates expired.
     */
    public function expireStaleMandate(): int
    {
        $staleThreshold = now()->subMonths(36);

        $count = SepaMandate::where('status', 'active')
            ->where(function ($query) use ($staleThreshold): void {
                $query->where(function ($q) use ($staleThreshold): void {
                    // Never used and signed more than 36 months ago
                    $q->whereNull('last_collection_at')
                        ->where('signed_at', '<', $staleThreshold);
                })->orWhere(function ($q) use ($staleThreshold): void {
                    // Last collection was more than 36 months ago
                    $q->whereNotNull('last_collection_at')
                        ->where('last_collection_at', '<', $staleThreshold);
                });
            })
            ->update(['status' => 'expired']);

        return $count;
    }

    /**
     * Find a mandate by mandate_id or throw ModelNotFoundException.
     */
    private function findByMandateIdOrFail(string $mandateId): SepaMandate
    {
        $mandate = $this->findByMandateId($mandateId);

        if ($mandate === null) {
            throw new ModelNotFoundException("SEPA mandate [{$mandateId}] not found.");
        }

        return $mandate;
    }
}
