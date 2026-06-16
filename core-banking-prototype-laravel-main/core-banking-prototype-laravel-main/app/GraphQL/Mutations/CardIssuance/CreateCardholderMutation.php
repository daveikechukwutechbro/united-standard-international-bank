<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\CardIssuance;

use App\Domain\CardIssuance\Models\Cardholder;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class CreateCardholderMutation
{
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

        $cardholder = Cardholder::create([
            'user_id'                => $user->id,
            'first_name'             => $args['first_name'],
            'last_name'              => $args['last_name'],
            'email'                  => $args['email'] ?? null,
            'phone'                  => $args['phone'] ?? null,
            'shipping_address_line1' => $args['shipping_address_line1'] ?? null,
            'shipping_city'          => $args['shipping_city'] ?? null,
            'shipping_country'       => $args['shipping_country'] ?? null,
            'kyc_status'             => 'pending',
        ]);

        return [
            'id'          => $cardholder->id,
            'first_name'  => $cardholder->first_name,
            'last_name'   => $cardholder->last_name,
            'full_name'   => $cardholder->getFullName(),
            'email'       => $cardholder->email,
            'kyc_status'  => $cardholder->kyc_status,
            'is_verified' => $cardholder->isVerified(),
            'card_count'  => 0,
            'created_at'  => $cardholder->created_at?->toDateTimeString(),
        ];
    }
}
