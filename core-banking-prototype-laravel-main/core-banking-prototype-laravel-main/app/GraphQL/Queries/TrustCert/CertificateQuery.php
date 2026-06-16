<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\TrustCert;

use App\Domain\TrustCert\Models\Certificate;
use Illuminate\Database\Eloquent\Builder;

class CertificateQuery
{
    /**
     * @return Builder<Certificate>
     */
    public function __invoke(mixed $rootValue, array $args): Builder
    {
        return Certificate::query()->orderBy('created_at', 'desc');
    }
}
