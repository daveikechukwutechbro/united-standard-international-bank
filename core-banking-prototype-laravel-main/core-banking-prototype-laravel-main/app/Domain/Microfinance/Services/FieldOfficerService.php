<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Services;

use App\Domain\Microfinance\Models\CollectionSheet;
use App\Domain\Microfinance\Models\FieldOfficer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

class FieldOfficerService
{
    /**
     * Assign / register a new field officer.
     */
    public function assignOfficer(int $userId, string $name, ?string $territory = null): FieldOfficer
    {
        return FieldOfficer::create([
            'user_id'      => $userId,
            'name'         => $name,
            'territory'    => $territory,
            'client_count' => 0,
            'is_active'    => true,
        ]);
    }

    /**
     * Generate a collection sheet for a field officer and group.
     *
     * @throws RuntimeException
     */
    public function generateCollectionSheet(
        string $officerId,
        string $groupId,
        string $collectionDate,
        string $expectedAmount,
    ): CollectionSheet {
        $officer = FieldOfficer::find($officerId);

        if ($officer === null) {
            throw new RuntimeException("Field officer not found: {$officerId}");
        }

        return CollectionSheet::create([
            'officer_id'       => $officerId,
            'group_id'         => $groupId,
            'collection_date'  => $collectionDate,
            'expected_amount'  => $expectedAmount,
            'collected_amount' => '0.00',
            'status'           => 'pending',
        ]);
    }

    /**
     * Record the amount collected on a collection sheet.
     *
     * Sets status to 'completed' when collected_amount >= expected_amount.
     *
     * @param numeric-string $collectedAmount
     *
     * @throws RuntimeException
     */
    public function recordCollection(string $sheetId, string $collectedAmount): CollectionSheet
    {
        $sheet = CollectionSheet::find($sheetId);

        if ($sheet === null) {
            throw new RuntimeException("Collection sheet not found: {$sheetId}");
        }

        /** @var numeric-string $expectedAmt */
        $expectedAmt = sprintf('%.10f', (float) $sheet->expected_amount);
        $status = 'in_progress';
        if (bccomp($collectedAmount, $expectedAmt, 2) >= 0) {
            $status = 'completed';
        }

        $sheet->update([
            'collected_amount' => $collectedAmount,
            'status'           => $status,
        ]);

        return $sheet->fresh() ?? $sheet;
    }

    /**
     * Get collection sheets for a field officer, optionally filtered by date.
     *
     * @return Collection<int, CollectionSheet>
     *
     * @throws RuntimeException
     */
    public function getCollectionSheets(string $officerId, ?string $date = null): Collection
    {
        $officer = FieldOfficer::find($officerId);

        if ($officer === null) {
            throw new RuntimeException("Field officer not found: {$officerId}");
        }

        $query = CollectionSheet::where('officer_id', $officerId);

        if ($date !== null) {
            $query->whereDate('collection_date', $date);
        }

        return $query->get();
    }

    /**
     * Sync a field officer — update last_sync_at and client_count.
     *
     * @throws RuntimeException
     */
    public function syncOfficer(string $officerId): FieldOfficer
    {
        $officer = FieldOfficer::find($officerId);

        if ($officer === null) {
            throw new RuntimeException("Field officer not found: {$officerId}");
        }

        $clientCount = CollectionSheet::where('officer_id', $officerId)
            ->distinct('group_id')
            ->count('group_id');

        $officer->update([
            'last_sync_at' => Carbon::now(),
            'client_count' => $clientCount,
        ]);

        return $officer->fresh() ?? $officer;
    }
}
