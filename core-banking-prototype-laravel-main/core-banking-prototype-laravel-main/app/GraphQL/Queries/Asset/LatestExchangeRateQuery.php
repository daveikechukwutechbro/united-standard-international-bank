<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Asset;

use App\Domain\Asset\Models\ExchangeRate;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class LatestExchangeRateQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): ?ExchangeRate
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return ExchangeRate::query()
            ->where('from_asset_code', $args['from_asset_code'])
            ->where('to_asset_code', $args['to_asset_code'])
            ->where('is_active', true)
            ->orderBy('valid_at', 'desc')
            ->first();
    }
}
