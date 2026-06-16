<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Banking;

use App\Domain\Banking\Models\BankConnectionModel;
use App\Domain\Banking\Services\BankIntegrationService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class DisconnectBankMutation
{
    public function __construct(
        private readonly BankIntegrationService $bankIntegrationService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        /** @var BankConnectionModel $connection */
        $connection = BankConnectionModel::findOrFail($args['connection_id']);

        $this->bankIntegrationService->disconnectUserFromBank(
            $user,
            $connection->bank_code,
        );

        return true;
    }
}
