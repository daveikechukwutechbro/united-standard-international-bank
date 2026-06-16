<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\PaymentRails;

use App\Domain\PaymentRails\Services\PaymentRailRouter;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class SendPaymentMutation
{
    public function __construct(
        private readonly PaymentRailRouter $router,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null) {
            throw new AuthenticationException('Unauthenticated.');
        }

        /** @var array{name: string, account_number?: string, routing_number?: string, iban?: string, bic?: string} $beneficiary */
        $beneficiary = $args['beneficiary'];

        return $this->router->route(
            userId: $user->id,
            amount: (string) $args['amount'],
            currency: (string) $args['currency'],
            country: (string) $args['country'],
            urgency: isset($args['urgency']) ? (string) $args['urgency'] : 'normal',
            beneficiary: $beneficiary,
        );
    }
}
