<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Contracts;

use App\Domain\MobilePayment\Models\PaymentIntent;

interface PaymentIntentServiceInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(int $userId, array $data): PaymentIntent;

    public function get(string $intentId, int $userId): PaymentIntent;

    public function submit(string $intentId, int $userId, string $authType): PaymentIntent;

    public function cancel(string $intentId, int $userId, ?string $reason = null): PaymentIntent;
}
