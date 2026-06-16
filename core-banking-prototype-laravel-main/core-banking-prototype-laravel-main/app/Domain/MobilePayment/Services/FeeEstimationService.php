<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Services;

use App\Domain\MobilePayment\Enums\PaymentNetwork;
use App\Domain\MobilePayment\ValueObjects\FeeEstimate;

class FeeEstimationService
{
    /**
     * Estimate network fees for a payment on the given network.
     */
    public function estimate(PaymentNetwork $network, string $amount, bool $shieldEnabled): FeeEstimate
    {
        $base = FeeEstimate::forNetwork($network);

        if ($shieldEnabled) {
            // Shield transactions cost ~2x due to privacy overhead
            $multipliedAmount = bcmul($base->amount, '2', 8);
            $multipliedUsd = bcmul($base->usdApprox, '2', 2);

            return new FeeEstimate(
                nativeAsset: $base->nativeAsset,
                amount: $multipliedAmount,
                usdApprox: $multipliedUsd,
            );
        }

        return $base;
    }
}
