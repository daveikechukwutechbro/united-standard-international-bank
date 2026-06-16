<?php

namespace App\Domain\Account\Workflows;

use App\Domain\Account\Aggregates\LedgerAggregate;
use App\Domain\Account\DataObjects\AccountUuid;
use Workflow\Activity;

class UnfreezeAccountActivity extends Activity
{
    public function execute(
        AccountUuid $uuid,
        string $reason,
        ?string $authorizedBy,
        LedgerAggregate $ledger
    ): bool {
        $ledger->retrieve($uuid->getUuid())
            ->unfreezeAccount($reason, $authorizedBy)
            ->persist();

        return true;
    }
}
