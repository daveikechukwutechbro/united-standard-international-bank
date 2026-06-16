<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Batch;

use App\Domain\Batch\Models\BatchJob;
use App\Domain\Batch\Services\BatchProcessingService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class CreateBatchJobMutation
{
    public function __construct(
        private readonly BatchProcessingService $batchProcessingService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): BatchJob
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $batchJob = $this->batchProcessingService->createBatchJob(
            userUuid: $user->uuid ?? (string) $user->id,
            name: $args['name'],
            type: $args['type'],
            items: $args['items'] ?? [],
        );

        return $batchJob;
    }
}
