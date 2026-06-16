<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Microfinance;

use App\Domain\Microfinance\Models\ShareAccount;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ShareAccountsQuery
{
    /**
     * @param  array<string, mixed>  $args
     * @return Collection<int, ShareAccount>
     *
     * @throws AuthenticationException
     */
    public function __invoke(mixed $rootValue, array $args): Collection
    {
        $user = Auth::user();

        if ($user === null) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return ShareAccount::forUser($user->id)->get();
    }
}
