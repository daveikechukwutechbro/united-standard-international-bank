<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\MobilePayment;

use App\Domain\MobilePayment\Models\PaymentIntent;
use Illuminate\Database\Eloquent\Builder;

class PaymentIntentQuery
{
    /**
     * @return Builder<PaymentIntent>
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        return PaymentIntent::query()->orderBy('created_at', 'desc');
    }
}
