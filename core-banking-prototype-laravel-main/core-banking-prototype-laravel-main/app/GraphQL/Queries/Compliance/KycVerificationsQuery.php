<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Compliance;

use App\Domain\Compliance\Models\KycVerification;
use Illuminate\Database\Eloquent\Builder;

class KycVerificationsQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        return KycVerification::query()->orderBy('created_at', 'desc');
    }
}
