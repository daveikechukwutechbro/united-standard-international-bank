<?php

namespace App\Domain\Batch\Actions;

use App\Domain\Batch\Events\BatchJobCompleted;
use App\Domain\Batch\Models\BatchJob;

class CompleteBatchJob
{
    public function __invoke(BatchJobCompleted $event): void
    {
        BatchJob::where('uuid', $event->aggregateRootUuid())
            ->update(
                [
                    'status'       => $event->finalStatus,
                    'completed_at' => $event->completedAt,
                ]
            );
    }
}
