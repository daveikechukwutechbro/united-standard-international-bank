<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\PaymentRails;

use App\Domain\PaymentRails\Services\PaymentRailRouter;

final class TransactionQuery
{
    public function __construct(
        private readonly PaymentRailRouter $router,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>|null
     */
    public function __invoke(mixed $rootValue, array $args): ?array
    {
        return $this->router->getTransactionStatus((string) $args['id']);
    }
}
