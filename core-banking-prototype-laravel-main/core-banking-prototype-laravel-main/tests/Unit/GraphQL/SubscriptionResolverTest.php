<?php

declare(strict_types=1);

use App\GraphQL\Subscriptions\BridgeTransferCompletedSubscription;
use App\GraphQL\Subscriptions\OrderMatchedSubscription;
use App\GraphQL\Subscriptions\PaymentStatusChangedSubscription;
use App\GraphQL\Subscriptions\PortfolioRebalancedSubscription;

describe('GraphQL Subscription Resolvers', function () {
    it('instantiates OrderMatchedSubscription', function () {
        $subscription = new OrderMatchedSubscription();
        expect($subscription)->toBeInstanceOf(OrderMatchedSubscription::class);
    });

    it('instantiates PortfolioRebalancedSubscription', function () {
        $subscription = new PortfolioRebalancedSubscription();
        expect($subscription)->toBeInstanceOf(PortfolioRebalancedSubscription::class);
    });

    it('instantiates PaymentStatusChangedSubscription', function () {
        $subscription = new PaymentStatusChangedSubscription();
        expect($subscription)->toBeInstanceOf(PaymentStatusChangedSubscription::class);
    });

    it('instantiates BridgeTransferCompletedSubscription', function () {
        $subscription = new BridgeTransferCompletedSubscription();
        expect($subscription)->toBeInstanceOf(BridgeTransferCompletedSubscription::class);
    });
});
