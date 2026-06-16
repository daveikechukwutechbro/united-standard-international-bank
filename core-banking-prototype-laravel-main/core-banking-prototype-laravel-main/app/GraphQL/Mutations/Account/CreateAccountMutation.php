<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Account;

use App\Domain\Account\DataObjects\Account as AccountData;
use App\Domain\Account\Models\Account;
use App\Domain\Account\Workflows\CreateAccountWorkflow;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Workflow\WorkflowStub;

class CreateAccountMutation
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Account
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $accountUuid = Str::uuid()->toString();

        $accountData = new AccountData(
            name: $args['name'],
            userUuid: $user->uuid,
            uuid: $accountUuid,
        );

        $workflow = WorkflowStub::make(CreateAccountWorkflow::class);
        $workflow->start($accountData);

        // Return the read-model projection (created by projector),
        // or create a fallback record for immediate GraphQL response.
        /** @var Account $account */
        $account = Account::where('uuid', $accountUuid)->first()
            ?? Account::create([
                'uuid'      => $accountUuid,
                'name'      => $args['name'],
                'balance'   => 0,
                'frozen'    => false,
                'user_uuid' => $user->uuid,
            ]);

        return $account;
    }
}
