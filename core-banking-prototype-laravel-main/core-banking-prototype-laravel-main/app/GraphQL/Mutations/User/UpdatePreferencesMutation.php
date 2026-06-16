<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\User;

use App\Domain\User\Models\UserProfile;
use App\Domain\User\Services\UserProfileService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class UpdatePreferencesMutation
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
            data: [
                'preferences' => $args['preferences'] ?? [],
            ],
            updatedBy: $user->name ?? (string) $user->id,
        );

        return $profile;
    }
}
