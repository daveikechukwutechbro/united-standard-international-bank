<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Banking;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class BankTransfersQuery
{
    /**
     * Resolve a paginated list of bank transfers for the authenticated user.
     *
     * @param  array<string, mixed>  $args
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $limit = min((int) ($args['first'] ?? 15), 100);

        $transfers = DB::table('bank_transfers')
            ->where('user_uuid', $user->uuid)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $transfers->map(function (object $record): array {
            /** @var array<string, mixed> $metadata */
            $metadata = json_decode((string) ($record->metadata ?? '{}'), true) ?? [];

            return [
                'id'              => (string) $record->id,
                'from_account_id' => (string) $record->from_account_id,
                'to_account_id'   => (string) $record->to_account_id,
                'amount'          => (string) $record->amount,
                'currency'        => (string) $record->currency,
                'status'          => (string) $record->status,
                'reference'       => (string) ($metadata['reference'] ?? $record->reference ?? ''),
                'created_at'      => (string) ($record->created_at ?? ''),
            ];
        })->toArray();
    }
}
