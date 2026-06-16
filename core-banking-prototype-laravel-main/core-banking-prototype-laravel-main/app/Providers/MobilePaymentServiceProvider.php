<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\MobilePayment\Contracts\MerchantLookupServiceInterface;
use App\Domain\MobilePayment\Contracts\PaymentIntentServiceInterface;
use App\Domain\MobilePayment\Events\PaymentIntentCancelled;
use App\Domain\MobilePayment\Events\PaymentIntentConfirmed;
use App\Domain\MobilePayment\Events\PaymentIntentFailed;
use App\Domain\MobilePayment\Services\ActivityFeedProjector;
use App\Domain\MobilePayment\Services\DemoMerchantLookupService;
use App\Domain\MobilePayment\Services\PaymentIntentService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the MobilePayment domain.
 */
class MobilePaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PaymentIntentServiceInterface::class,
            PaymentIntentService::class,
        );

        // In production, swap DemoMerchantLookupService for a real implementation
        $this->app->bind(
            MerchantLookupServiceInterface::class,
            DemoMerchantLookupService::class,
        );
    }

    public function boot(): void
    {
        // Register activity feed projector for payment intent events
        Event::listen(PaymentIntentConfirmed::class, [ActivityFeedProjector::class, 'onPaymentIntentConfirmed']);
        Event::listen(PaymentIntentFailed::class, [ActivityFeedProjector::class, 'onPaymentIntentFailed']);
        Event::listen(PaymentIntentCancelled::class, [ActivityFeedProjector::class, 'onPaymentIntentCancelled']);
    }
}
