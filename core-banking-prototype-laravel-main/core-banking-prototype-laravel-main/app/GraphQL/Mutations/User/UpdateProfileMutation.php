<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\User;

use App\Domain\User\Models\UserProfile;
use App\Domain\User\Services\UserProfileService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class UpdateProfileMutation
{
    public function __construct(
        private readonly UserProfileService $userProfileService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): UserProfile
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $profile = $this->userProfileService->updateProfile(
            userId: (string) $user->id,
            data: array_filter([
                'first_name'   => $args['first_name'] ?? null,
                'last_name'    => $args['last_name'] ?? null,
                'phone_number' => $args['phone_number'] ?? null,
                'country'      => $args['country'] ?? null,
                'city'         => $args['city'] ?? null,
                'address'      => $args['address'] ?? null,
                'postal_code'  => $args['postal_code'] ?? null,
            ], fn ($value) => $value !== null),
            updatedBy: $user->name ?? (string) $user->id,
        );

        return $profile;
    }
}
