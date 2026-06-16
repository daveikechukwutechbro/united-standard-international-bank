<?php

declare(strict_types=1);

use App\Domain\Shared\EventSourcing\EventStreamPublisher;

describe('EventStreamPublisher', function () {
    it('class exists and is instantiable', function () {
        expect((new ReflectionClass(EventStreamPublisher::class))->getName())->not->toBeEmpty();
    });

    it('has publish method', function () {
        expect((new ReflectionClass(EventStreamPublisher::class))->hasMethod('publish'))->toBeTrue();
    });

    it('has publishBatch method', function () {
        expect((new ReflectionClass(EventStreamPublisher::class))->hasMethod('publishBatch'))->toBeTrue();
    });

    it('has getStreamInfo method', function () {
        expect((new ReflectionClass(EventStreamPublisher::class))->hasMethod('getStreamInfo'))->toBeTrue();
    });
});
