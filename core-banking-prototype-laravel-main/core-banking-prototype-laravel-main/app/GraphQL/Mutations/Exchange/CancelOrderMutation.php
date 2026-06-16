<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Exchange;

use App\Domain\Exchange\Projections\Order;
use App\Domain\Exchange\Services\ExchangeService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class CancelOrderMutation
{
    public function __construct(
        private readonly ExchangeService $exchangeService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Order
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        /** @var Order|null $order */
        $order = Order::query()->where('order_id', $args['order_id'])->first();

        if (! $order) {
            throw new ModelNotFoundException('Order not found.');
        }

        $reason = $args['reason'] ?? 'Cancelled via GraphQL';

        $this->exchangeService->cancelOrder($args['order_id'], $reason);

        return $order->fresh() ?? $order;
    }
}
