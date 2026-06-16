<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\CardIssuance;

use App\Domain\CardIssuance\Enums\CardNetwork;
use App\Domain\CardIssuance\Services\CardProvisioningService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class CreateCardMutation
{
    public function __construct(
        private readonly CardProvisioningService $cardProvisioningService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $network = isset($args['network'])
            ? CardNetwork::from($args['network'])
            : null;

        $card = $this->cardProvisioningService->createCard(
            userId: (string) $user->id,
            cardholderName: $args['cardholder_id'],
            metadata: [],
            network: $network,
            label: $args['label'] ?? null,
        );

        return [
            'id'                   => $card->cardToken,
            'card_token'           => $card->cardToken,
            'cardholder_name'      => $card->cardholderName,
            'last_four'            => $card->last4,
            'network'              => $card->network->value,
            'status'               => $card->status->value,
            'currency'             => $args['currency'] ?? 'USD',
            'label'                => $card->label,
            'issuer'               => null,
            'funding_source'       => null,
            'spend_limit_interval' => null,
            'expires_at'           => $card->expiresAt->format('Y-m-d'),
            'created_at'           => now()->toDateTimeString(),
        ];
    }
}
