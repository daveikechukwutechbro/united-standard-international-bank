<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Mobile;

use App\Domain\Mobile\Models\MobileDevice;
use Illuminate\Database\Eloquent\Builder;

class MobileDeviceQuery
{
    /**
     * @return Builder<MobileDevice>
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        return MobileDevice::query()->orderBy('created_at', 'desc');
    }
}
