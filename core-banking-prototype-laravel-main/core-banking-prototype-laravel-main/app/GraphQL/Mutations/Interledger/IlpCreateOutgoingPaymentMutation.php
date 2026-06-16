<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Interledger;

use App\Domain\Interledger\Services\OpenPaymentsService;

class IlpCreateOutgoingPaymentMutation
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
        $result = $this->openPaymentsService->createOutgoingPayment(
            walletAddress: (string) $args['wallet_address'],
            quoteId: (string) $args['quote_id'],
            grantToken: (string) $args['grant_token'],
        );

        return [
            'payment_id' => $result['outgoing_payment_id'],
            'status'     => $result['status'],
            'checked_at' => $result['created_at'],
        ];
    }
}
