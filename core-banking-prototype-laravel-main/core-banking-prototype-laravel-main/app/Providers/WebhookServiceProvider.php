<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class WebhookServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Override Cashier's webhook route to add signature validation
        $this->overrideCashierWebhookRoute();
    }

    /**
     * Override Laravel Cashier's webhook route to add signature validation.
     */
    private function overrideCashierWebhookRoute(): void
    {
        // This will override the default Cashier webhook route
        Route::post(
            'stripe/webhook',
            [\App\Http\Controllers\StripeWebhookController::class, 'handleWebhook']
        )->middleware(['webhook.signature:stripe'])->name('cashier.webhook');
    }
}
