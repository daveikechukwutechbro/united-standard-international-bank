<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\CardIssuance;

use App\Domain\CardIssuance\Services\CardProvisioningService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class FreezeCardMutation
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

        $cardToken = $args['id'];

        $this->cardProvisioningService->freezeCard($cardToken);

        $card = $this->cardProvisioningService->getCard($cardToken);

        if ($card === null) {
            throw new RuntimeException('Card not found after freeze operation.');
        }

        return [
            'id'                   => $card->cardToken,
            'card_token'           => $card->cardToken,
            'cardholder_name'      => $card->cardholderName,
            'last_four'            => $card->last4,
            'network'              => $card->network->value,
            'status'               => $card->status->value,
            'currency'             => null,
            'label'                => $card->label,
            'issuer'               => null,
            'funding_source'       => null,
            'spend_limit_interval' => null,
            'expires_at'           => $card->expiresAt->format('Y-m-d'),
            'created_at'           => now()->toDateTimeString(),
        ];
    }
}
