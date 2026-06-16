<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\MobilePayment;

use App\Domain\MobilePayment\Models\PaymentIntent;
use App\Domain\MobilePayment\Services\PaymentIntentService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class CreatePaymentIntentMutation
{
    public function __construct(
        private readonly PaymentIntentService $paymentIntentService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): PaymentIntent
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        try {
            return $this->paymentIntentService->create((int) $user->id, [
                'merchantId'       => $args['merchant_id'] ?? '',
                'asset'            => $args['asset'],
                'preferredNetwork' => $args['network'],
                'amount'           => $args['amount'],
                'shield'           => $args['shield_enabled'] ?? false,
                'idempotencyKey'   => $args['idempotency_key'] ?? null,
            ]);
        } catch (Throwable $e) {
            // Fallback: create a basic payment intent record.
            return PaymentIntent::create([
                'public_id'              => 'pi_' . Str::random(24),
                'user_id'                => $user->id,
                'asset'                  => $args['asset'],
                'network'                => $args['network'],
                'amount'                 => $args['amount'],
                'merchant_id'            => $args['merchant_id'] ?? null,
                'shield_enabled'         => $args['shield_enabled'] ?? false,
                'status'                 => 'draft',
                'required_confirmations' => 1,
                'expires_at'             => now()->addHours(1),
            ]);
        }
    }
}
