<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Batch;

use App\Domain\Batch\Models\BatchJob;

class BatchJobQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): BatchJob
    {
        /** @var BatchJob */
        return BatchJob::findOrFail($args['id']);
    }
}
