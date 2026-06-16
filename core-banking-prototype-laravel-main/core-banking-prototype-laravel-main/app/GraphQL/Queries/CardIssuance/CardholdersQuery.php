<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\CardIssuance;

use App\Domain\CardIssuance\Models\Cardholder;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class CardholdersQuery
{
    /**
     * @param  array<string, mixed>  $args
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $cardholders = Cardholder::where('user_id', $user->id)->get();

        return $cardholders->map(fn (Cardholder $cardholder) => [
            'id'          => $cardholder->id,
            'first_name'  => $cardholder->first_name,
            'last_name'   => $cardholder->last_name,
            'full_name'   => $cardholder->getFullName(),
            'email'       => $cardholder->email,
            'kyc_status'  => $cardholder->kyc_status,
            'is_verified' => $cardholder->isVerified(),
            'card_count'  => $cardholder->cards()->count(),
            'created_at'  => $cardholder->created_at?->toDateTimeString(),
        ])->all();
    }
}
