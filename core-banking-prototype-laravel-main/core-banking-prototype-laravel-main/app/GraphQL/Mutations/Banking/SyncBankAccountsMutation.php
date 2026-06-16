<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Banking;

use App\Domain\Banking\Models\BankAccountModel;
use App\Domain\Banking\Models\BankConnectionModel;
use App\Domain\Banking\Services\BankIntegrationService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

final class SyncBankAccountsMutation
{
    public function __construct(
        private readonly BankIntegrationService $bankIntegrationService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return Collection<int, BankAccountModel>
     */
    public function __invoke(mixed $rootValue, array $args): Collection
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        /** @var BankConnectionModel $connection */
        $connection = BankConnectionModel::findOrFail($args['connection_id']);

        $this->bankIntegrationService->syncBankAccounts(
            $user,
            $connection->bank_code,
        );

        return BankAccountModel::query()
            ->where('user_uuid', $user->uuid)
            ->where('bank_code', $connection->bank_code)
            ->get();
    }
}
