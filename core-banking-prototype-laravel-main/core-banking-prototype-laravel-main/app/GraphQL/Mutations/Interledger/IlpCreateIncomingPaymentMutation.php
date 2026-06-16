<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Interledger;

use App\Domain\Interledger\Services\OpenPaymentsService;

class IlpCreateIncomingPaymentMutation
{
    public function __construct(
        private readonly OpenPaymentsService $openPaymentsService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): string
    {
        $result = $this->openPaymentsService->createIncomingPayment(
            walletAddress: (string) $args['wallet_address'],
            amount: (string) $args['amount'],
            assetCode: strtoupper((string) $args['asset_code']),
        );

        return $result['incoming_payment_id'];
    }
}
