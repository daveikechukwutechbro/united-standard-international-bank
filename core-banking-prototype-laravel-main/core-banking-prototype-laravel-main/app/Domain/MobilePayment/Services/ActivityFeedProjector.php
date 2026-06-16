<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Services;

use App\Domain\MobilePayment\Enums\ActivityItemType;
use App\Domain\MobilePayment\Events\PaymentIntentCancelled;
use App\Domain\MobilePayment\Events\PaymentIntentConfirmed;
use App\Domain\MobilePayment\Events\PaymentIntentFailed;
use App\Domain\MobilePayment\Models\ActivityFeedItem;
use App\Domain\MobilePayment\Models\PaymentIntent;

/**
 * Projects payment intent state changes into the activity feed read model.
 */
class ActivityFeedProjector
{
    public function onPaymentIntentConfirmed(PaymentIntentConfirmed $event): void
    {
        $intent = $event->intent;
        $intent->loadMissing('merchant');

        ActivityFeedItem::create([
            'user_id'           => $intent->user_id,
            'activity_type'     => ActivityItemType::MERCHANT_PAYMENT,
            'merchant_name'     => $intent->merchant?->display_name,
            'merchant_icon_url' => $intent->merchant?->icon_url,
            'amount'            => '-' . $intent->amount,
            'asset'             => $intent->asset,
            'network'           => $intent->network,
            'status'            => 'confirmed',
            'protected'         => $intent->shield_enabled,
            'reference_type'    => PaymentIntent::class,
            'reference_id'      => $intent->id,
            'occurred_at'       => $intent->confirmed_at ?? now(),
        ]);
    }

    public function onPaymentIntentFailed(PaymentIntentFailed $event): void
    {
        $this->updateOrCreateFeedItem($event->intent, 'failed');
    }

    public function onPaymentIntentCancelled(PaymentIntentCancelled $event): void
    {
        $this->updateOrCreateFeedItem($event->intent, 'cancelled');
    }

    private function updateOrCreateFeedItem(PaymentIntent $intent, string $status): void
    {
        $existing = ActivityFeedItem::where('reference_type', PaymentIntent::class)
            ->where('reference_id', $intent->id)
            ->first();

        if ($existing) {
            $existing->update(['status' => $status]);

            return;
        }

        $intent->loadMissing('merchant');

        ActivityFeedItem::create([
            'user_id'           => $intent->user_id,
            'activity_type'     => ActivityItemType::MERCHANT_PAYMENT,
            'merchant_name'     => $intent->merchant?->display_name,
            'merchant_icon_url' => $intent->merchant?->icon_url,
            'amount'            => '-' . $intent->amount,
            'asset'             => $intent->asset,
            'network'           => $intent->network,
            'status'            => $status,
            'protected'         => $intent->shield_enabled,
            'reference_type'    => PaymentIntent::class,
            'reference_id'      => $intent->id,
            'occurred_at'       => now(),
        ]);
    }
}
