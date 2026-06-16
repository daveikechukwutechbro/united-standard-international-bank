<?php

declare(strict_types=1);

use App\Domain\Monitoring\Services\LiveMetricsService;

describe('LiveMetricsService', function () {
    it('class exists', function () {
        expect((new ReflectionClass(LiveMetricsService::class))->getName())->not->toBeEmpty();
    });

    it('has getMetrics method', function () {
        expect((new ReflectionClass(LiveMetricsService::class))->hasMethod('getMetrics'))->toBeTrue();
    });

    it('has getDomainHealth method', function () {
        expect((new ReflectionClass(LiveMetricsService::class))->hasMethod('getDomainHealth'))->toBeTrue();
    });

    it('has getEventThroughput method', function () {
        expect((new ReflectionClass(LiveMetricsService::class))->hasMethod('getEventThroughput'))->toBeTrue();
    });

    it('has getStreamStatus method', function () {
        expect((new ReflectionClass(LiveMetricsService::class))->hasMethod('getStreamStatus'))->toBeTrue();
    });

    it('has getSystemHealth method', function () {
        expect((new ReflectionClass(LiveMetricsService::class))->hasMethod('getSystemHealth'))->toBeTrue();
    });

    it('has getProjectorLag method', function () {
        expect((new ReflectionClass(LiveMetricsService::class))->hasMethod('getProjectorLag'))->toBeTrue();
    });
});
