<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Events;

use App\Domain\MobilePayment\Models\PaymentIntent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentIntentFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly PaymentIntent $intent,
    ) {
    }
}
