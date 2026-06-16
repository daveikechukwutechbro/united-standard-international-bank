<?php

declare(strict_types=1);

use App\Domain\Compliance\Services\Certification\DataRetentionService;

describe('DataRetentionService', function () {
    it('can be instantiated', function () {
        $service = new DataRetentionService();
        expect($service)->toBeInstanceOf(DataRetentionService::class);
    });

    it('returns demo retention summary', function () {
        $service = new DataRetentionService();
        $demo = $service->getDemoSummary();

        expect($demo)
            ->toHaveKey('total_policies')
            ->toHaveKey('enabled')
            ->toHaveKey('by_action')
            ->toHaveKey('policies');
    });

    it('gets retention policies', function () {
        $service = new DataRetentionService();
        $policies = $service->getPolicies();

        expect($policies)->toBeInstanceOf(Illuminate\Support\Collection::class);
    });

    it('gets retention summary', function () {
        $service = new DataRetentionService();
        $summary = $service->getSummary();

        expect($summary)
            ->toHaveKey('total_policies')
            ->toHaveKey('enabled')
            ->toHaveKey('disabled')
            ->toHaveKey('by_action');
    });

    it('enforces policies in dry-run mode', function () {
        $service = new DataRetentionService();
        $results = $service->enforceRetentionPolicies(dryRun: true);

        expect($results)
            ->toHaveKey('dry_run')
            ->toHaveKey('policies_run')
            ->toHaveKey('total_affected')
            ->and($results['dry_run'])->toBeTrue();
    });
});
