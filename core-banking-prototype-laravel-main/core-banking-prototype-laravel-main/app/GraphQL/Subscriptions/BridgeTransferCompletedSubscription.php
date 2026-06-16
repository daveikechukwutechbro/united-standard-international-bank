<?php

declare(strict_types=1);

namespace App\GraphQL\Subscriptions;

use Illuminate\Http\Request;
use Nuwave\Lighthouse\Schema\Types\GraphQLSubscription;
use Nuwave\Lighthouse\Subscriptions\Subscriber;

class BridgeTransferCompletedSubscription extends GraphQLSubscription
{
    public function authorize(Subscriber $subscriber, Request $request): bool
    {
        return (bool) $subscriber->context->user();
    }

    public function filter(Subscriber $subscriber, mixed $root): bool
    {
        $args = $subscriber->args;
        $transferId = $args['transfer_id'] ?? null;

        if ($transferId === null) {
            return true;
        }

        return isset($root['transfer_id']) && (string) $root['transfer_id'] === (string) $transferId;
    }
}
