<?php

declare(strict_types=1);

namespace App\Domain\Asset\Workflows\Activities;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Asset\Aggregates\AssetTransferAggregate;
use Workflow\Activity;

class CompleteAssetTransferActivity extends Activity
{
    /**
     * Execute complete asset transfer activity.
     */
    public function execute(
        string $transferId,
        AccountUuid $fromAccountUuid,
        AccountUuid $toAccountUuid,
        string $fromAssetCode,
        string $toAssetCode,
        Money $fromAmount,
        Money $toAmount,
        ?float $exchangeRate = null
    ): bool {
        // Complete the transfer using the aggregate
        AssetTransferAggregate::retrieve($transferId)
            ->complete(
                transferId: $transferId,
                metadata: [
                    'workflow'           => 'AssetTransferWorkflow',
                    'activity'           => 'CompleteAssetTransferActivity',
                    'completed_at'       => now()->toISOString(),
                    'exchange_rate_used' => $exchangeRate,
                    'final_from_amount'  => $fromAmount->getAmount(),
                    'final_to_amount'    => $toAmount->getAmount(),
                ]
            )
            ->persist();

        return true;
    }
}
