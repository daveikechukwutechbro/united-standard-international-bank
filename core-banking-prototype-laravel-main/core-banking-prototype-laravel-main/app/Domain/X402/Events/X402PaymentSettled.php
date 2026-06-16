<?php

declare(strict_types=1);

namespace App\Domain\X402\Events;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class X402PaymentSettled
{
    use Dispatchable;
    use SerializesModels;

    public readonly CarbonImmutable $occurredAt;

    public function __construct(
        public readonly string $paymentId,
        public readonly string $payerAddress,
        public readonly string $transactionHash,
        public readonly string $network,
        public readonly string $amount,
        ?CarbonImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new CarbonImmutable();
    }
}
