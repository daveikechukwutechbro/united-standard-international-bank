<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Interledger;

use App\Domain\Interledger\Services\OpenPaymentsService;

class IlpPaymentQuery
{
    public function __construct(
        private readonly OpenPaymentsService $openPaymentsService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{payment_id: string, status: string, checked_at: string}
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        return $this->openPaymentsService->getPaymentStatus((string) $args['id']);
    }
}
