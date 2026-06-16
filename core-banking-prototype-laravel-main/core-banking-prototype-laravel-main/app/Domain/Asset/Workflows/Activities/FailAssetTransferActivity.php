<?php

declare(strict_types=1);

namespace App\Domain\Asset\Workflows\Activities;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Asset\Aggregates\AssetTransferAggregate;
use Workflow\Activity;

class FailAssetTransferActivity extends Activity
{
    /**
     * Execute fail asset transfer activity.
     */
    public function execute(
        ?string $transferId,
        AccountUuid $fromAccountUuid,
        AccountUuid $toAccountUuid,
        string $fromAssetCode,
        string $toAssetCode,
        Money $fromAmount,
        string $reason
    ): bool {
        // Only fail if we have a transfer ID (transfer was initiated)
        if ($transferId) {
            AssetTransferAggregate::retrieve($transferId)
                ->fail(
                    reason: $reason,
                    transferId: $transferId,
                    metadata: [
                        'workflow'       => 'AssetTransferWorkflow',
                        'activity'       => 'FailAssetTransferActivity',
                        'failed_at'      => now()->toISOString(),
                        'failure_reason' => $reason,
                    ]
                )
                ->persist();
        }

        return true;
    }
}
