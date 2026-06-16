<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Jobs;

use App\Domain\MobilePayment\Enums\PaymentIntentStatus;
use App\Domain\MobilePayment\Models\PaymentIntent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Expire stale payment intents that haven't been submitted.
 *
 * Runs every minute to catch intents that nobody is polling.
 */
class ExpireStalePaymentIntents implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $count = 0;

        PaymentIntent::expirable()->chunkById(200, function ($intents) use (&$count): void {
            foreach ($intents as $intent) {
                try {
                    $intent->transitionTo(PaymentIntentStatus::EXPIRED);
                    $count++;
                } catch (Throwable $e) {
                    Log::warning('Failed to expire payment intent', [
                        'intent_id' => $intent->id,
                        'status'    => $intent->status->value,
                        'error'     => $e->getMessage(),
                    ]);
                }
            }
        });

        if ($count > 0) {
            Log::info("Expired {$count} stale payment intents.");
        }
    }
}
