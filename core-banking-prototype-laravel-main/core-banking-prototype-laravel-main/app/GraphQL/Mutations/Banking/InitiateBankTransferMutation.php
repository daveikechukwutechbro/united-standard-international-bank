<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Banking;

use App\Domain\Banking\Models\BankAccountModel;
use App\Domain\Banking\Services\BankIntegrationService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class InitiateBankTransferMutation
{
    public function __construct(
        private readonly BankIntegrationService $bankIntegrationService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): string
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        /** @var BankAccountModel $fromAccount */
        $fromAccount = BankAccountModel::findOrFail($args['from_account_id']);

        $result = $this->bankIntegrationService->initiateInterBankTransfer(
            user: $user,
            fromBankCode: $fromAccount->bank_code,
            fromAccountId: $fromAccount->external_id ?? (string) $fromAccount->id,
            toBankCode: $fromAccount->bank_code,
            toAccountId: $args['to_iban'],
            amount: (float) $args['amount'],
            currency: $args['currency'],
            metadata: ['reference' => $args['reference'] ?? null],
        );

        return json_encode($result->toArray()) ?: '{}';
    }
}
