<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugins;

use App\Domain\Account\Events\AccountCreated;
use App\Domain\Exchange\Events\OrderMatched;
use App\Domain\Exchange\Events\OrderPlaced;
use App\Domain\Payment\Events\DepositCompleted;
use App\Domain\Payment\Events\DepositFailed;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

class PluginHookReactor extends Reactor
{
    public function __construct(
        private readonly PluginHookManager $hookManager,
    ) {
    }

    public function onAccountCreated(AccountCreated $event): void
    {
        $this->hookManager->dispatch('account.created', [
            'account_uuid' => $event->account->getUuid(),
            'account_name' => $event->account->getName(),
        ]);
    }

    public function onDepositCompleted(DepositCompleted $event): void
    {
        $this->hookManager->dispatch('payment.completed', [
            'transaction_id' => $event->transactionId,
            'completed_at'   => $event->completedAt->toIso8601String(),
        ]);
    }

    public function onDepositFailed(DepositFailed $event): void
    {
        $this->hookManager->dispatch('payment.failed', [
            'reason'    => $event->reason,
            'failed_at' => $event->failedAt->toIso8601String(),
        ]);
    }

    public function onOrderPlaced(OrderPlaced $event): void
    {
        $this->hookManager->dispatch('order.placed', [
            'order_id'       => $event->orderId,
            'account_id'     => $event->accountId,
            'type'           => $event->type,
            'order_type'     => $event->orderType,
            'base_currency'  => $event->baseCurrency,
            'quote_currency' => $event->quoteCurrency,
            'amount'         => $event->amount,
            'price'          => $event->price,
        ]);
    }

    public function onOrderMatched(OrderMatched $event): void
    {
        $this->hookManager->dispatch('order.matched', [
            'order_id'         => $event->orderId,
            'matched_order_id' => $event->matchedOrderId,
            'trade_id'         => $event->tradeId,
            'executed_price'   => $event->executedPrice,
            'executed_amount'  => $event->executedAmount,
        ]);
    }
}
