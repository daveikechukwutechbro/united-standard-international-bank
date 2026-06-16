<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Interledger;

use App\Domain\Interledger\Services\QuoteService;

class IlpCreateQuoteMutation
{
    public function __construct(
        private readonly QuoteService $quoteService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{send_asset: string, send_amount: string, receive_asset: string, receive_amount: string, exchange_rate: float, fee: string, expires_at: string}
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        return $this->quoteService->getQuote(
            sendAsset: strtoupper((string) $args['send_asset']),
            receiveAsset: strtoupper((string) $args['receive_asset']),
            sendAmount: (string) $args['send_amount'],
        );
    }
}
