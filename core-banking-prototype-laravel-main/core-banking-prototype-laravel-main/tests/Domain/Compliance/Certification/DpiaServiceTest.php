<?php

declare(strict_types=1);

use App\Domain\Compliance\Services\Certification\DpiaService;

describe('DpiaService', function () {
    it('can be instantiated', function () {
        $service = new DpiaService();
        expect($service)->toBeInstanceOf(DpiaService::class);
    });

    it('returns demo DPIA summary', function () {
        $service = new DpiaService();
        $demo = $service->getDemoSummary();

        expect($demo)
            ->toHaveKey('total')
            ->toHaveKey('by_status')
            ->toHaveKey('high_risk')
            ->toHaveKey('assessments')
            ->and($demo['total'])->toBe(3);
    });

    it('gets assessments list', function () {
        $service = new DpiaService();
        $assessments = $service->getAssessments();

        expect($assessments)->toBeInstanceOf(Illuminate\Support\Collection::class);
    });

    it('gets summary statistics', function () {
        $service = new DpiaService();
        $summary = $service->getSummary();

        expect($summary)
            ->toHaveKey('total')
            ->toHaveKey('high_risk')
            ->toHaveKey('average_score');
    });
});
