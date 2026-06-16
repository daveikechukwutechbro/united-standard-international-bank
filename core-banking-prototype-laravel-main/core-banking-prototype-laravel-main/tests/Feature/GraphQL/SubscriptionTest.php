<?php

declare(strict_types=1);

use App\GraphQL\Subscriptions\OrderBookUpdatedSubscription;
use App\GraphQL\Subscriptions\TradeExecutedSubscription;
use App\GraphQL\Subscriptions\WalletBalanceUpdatedSubscription;

describe('GraphQL Subscriptions', function () {
    it('instantiates OrderBookUpdatedSubscription', function () {
        $subscription = new OrderBookUpdatedSubscription();
        expect($subscription)->toBeInstanceOf(OrderBookUpdatedSubscription::class);
    });

    it('instantiates TradeExecutedSubscription', function () {
        $subscription = new TradeExecutedSubscription();
        expect($subscription)->toBeInstanceOf(TradeExecutedSubscription::class);
    });

    it('instantiates WalletBalanceUpdatedSubscription', function () {
        $subscription = new WalletBalanceUpdatedSubscription();
        expect($subscription)->toBeInstanceOf(WalletBalanceUpdatedSubscription::class);
    });
});
