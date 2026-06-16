<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Payment;

use App\Domain\Payment\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Builder;

class PaymentQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        return PaymentTransaction::query()->orderBy('created_at', 'desc');
    }
}
