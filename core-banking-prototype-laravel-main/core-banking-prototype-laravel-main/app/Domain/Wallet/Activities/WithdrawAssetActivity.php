<?php

namespace App\Domain\Wallet\Activities;

use App\Domain\Account\Aggregates\AssetTransactionAggregate;
use App\Domain\Account\DataObjects\AccountUuid;
use Workflow\Activity;

class WithdrawAssetActivity extends Activity
{
    public function execute(
        AccountUuid $accountUuid,
        string $assetCode,
        int $amount,
        AssetTransactionAggregate $assetTransaction
    ): bool {
        $assetTransaction->retrieve((string) $accountUuid)
            ->debit($assetCode, $amount)
            ->persist();

        return true;
    }
}
