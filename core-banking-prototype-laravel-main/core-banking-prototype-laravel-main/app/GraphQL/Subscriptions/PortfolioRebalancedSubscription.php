<?php

declare(strict_types=1);

namespace App\GraphQL\Subscriptions;

use Illuminate\Http\Request;
use Nuwave\Lighthouse\Schema\Types\GraphQLSubscription;
use Nuwave\Lighthouse\Subscriptions\Subscriber;

class PortfolioRebalancedSubscription extends GraphQLSubscription
{
    public function authorize(Subscriber $subscriber, Request $request): bool
    {
        return (bool) $subscriber->context->user();
    }

    public function filter(Subscriber $subscriber, mixed $root): bool
    {
        $args = $subscriber->args;
        $portfolioId = $args['portfolio_id'] ?? null;

        if ($portfolioId === null) {
            return true;
        }

        return isset($root['portfolio_id']) && $root['portfolio_id'] === $portfolioId;
    }
}
