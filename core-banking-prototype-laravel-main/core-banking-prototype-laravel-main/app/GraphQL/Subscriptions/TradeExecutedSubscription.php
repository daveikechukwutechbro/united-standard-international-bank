<?php

declare(strict_types=1);

namespace App\GraphQL\Subscriptions;

use Illuminate\Http\Request;
use Nuwave\Lighthouse\Schema\Types\GraphQLSubscription;
use Nuwave\Lighthouse\Subscriptions\Subscriber;

class TradeExecutedSubscription extends GraphQLSubscription
{
    public function authorize(Subscriber $subscriber, Request $request): bool
    {
        return (bool) $subscriber->context->user();
    }

    public function filter(Subscriber $subscriber, mixed $root): bool
    {
        $args = $subscriber->args;

        return isset($root['pair']) && $root['pair'] === ($args['pair'] ?? null);
    }
}
