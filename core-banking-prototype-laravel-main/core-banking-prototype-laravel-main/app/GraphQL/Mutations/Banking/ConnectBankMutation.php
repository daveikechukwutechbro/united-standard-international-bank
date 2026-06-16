<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Banking;

use App\Domain\Banking\Models\BankConnectionModel;
use App\Domain\Banking\Services\BankIntegrationService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class ConnectBankMutation
{
    public function __construct(
        private readonly BankIntegrationService $bankIntegrationService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): BankConnectionModel
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $credentials = json_decode($args['credentials'], true) ?: [];

        $connection = $this->bankIntegrationService->connectUserToBank(
            $user,
            $args['bank_code'],
            $credentials,
        );

        /** @var BankConnectionModel */
        return BankConnectionModel::query()
            ->where('user_uuid', $user->uuid)
            ->where('bank_code', $args['bank_code'])
            ->where('status', 'active')
            ->latest()
            ->firstOrFail();
    }
}
