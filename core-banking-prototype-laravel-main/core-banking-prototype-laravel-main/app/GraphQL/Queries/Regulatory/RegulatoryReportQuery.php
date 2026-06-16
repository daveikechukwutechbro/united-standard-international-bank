<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Regulatory;

use App\Domain\Regulatory\Models\RegulatoryReport;

class RegulatoryReportQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): RegulatoryReport
    {
        /** @var RegulatoryReport */
        return RegulatoryReport::findOrFail($args['id']);
    }
}
