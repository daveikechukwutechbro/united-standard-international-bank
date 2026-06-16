<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\X402;

use App\Domain\X402\Models\X402Payment;

class X402PaymentQuery
{
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     */
    public function __invoke($_, array $args): ?X402Payment
    {
        $teamId = auth()->user()?->currentTeam?->id;

        /** @var X402Payment|null */
        return X402Payment::where('team_id', $teamId)->find($args['id']);
    }
}
