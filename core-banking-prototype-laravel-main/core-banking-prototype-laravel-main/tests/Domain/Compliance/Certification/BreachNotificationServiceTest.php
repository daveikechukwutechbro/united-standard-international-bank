<?php

declare(strict_types=1);

use App\Domain\Compliance\Services\Certification\BreachNotificationService;

describe('BreachNotificationService', function () {
    it('can be instantiated', function () {
        $service = new BreachNotificationService();
        expect($service)->toBeInstanceOf(BreachNotificationService::class);
    });

    it('returns demo breach summary', function () {
        $service = new BreachNotificationService();
        $demo = $service->getDemoSummary();

        expect($demo)
            ->toHaveKey('total')
            ->toHaveKey('open')
            ->toHaveKey('by_severity')
            ->toHaveKey('overdue_notifications')
            ->and($demo['overdue_notifications'])->toBe(0);
    });

    it('gets breach summary statistics', function () {
        $service = new BreachNotificationService();
        $summary = $service->getSummary();

        expect($summary)
            ->toHaveKey('total')
            ->toHaveKey('open')
            ->toHaveKey('by_severity');
    });

    it('checks deadlines', function () {
        $service = new BreachNotificationService();
        $deadlines = $service->checkDeadlines();

        expect($deadlines)
            ->toHaveKey('overdue_count')
            ->toHaveKey('approaching_count')
            ->toHaveKey('checked_at');
    });

    it('gets breaches list', function () {
        $service = new BreachNotificationService();
        $breaches = $service->getBreaches();

        expect($breaches)->toBeInstanceOf(Illuminate\Support\Collection::class);
    });
});
