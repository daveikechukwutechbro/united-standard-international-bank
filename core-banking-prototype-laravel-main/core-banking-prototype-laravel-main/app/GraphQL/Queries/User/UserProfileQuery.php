<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\User;

use App\Domain\User\Models\UserProfile;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class UserProfileQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): ?UserProfile
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        /** @var UserProfile|null */
        return UserProfile::where('user_id', $user->id)->first();
    }
}
