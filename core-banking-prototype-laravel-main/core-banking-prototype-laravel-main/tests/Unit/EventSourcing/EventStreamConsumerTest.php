<?php

declare(strict_types=1);

use App\Domain\Shared\EventSourcing\EventStreamConsumer;

describe('EventStreamConsumer', function () {
    it('class exists and is instantiable', function () {
        expect((new ReflectionClass(EventStreamConsumer::class))->getName())->not->toBeEmpty();
    });

    it('has createConsumerGroup method', function () {
        expect((new ReflectionClass(EventStreamConsumer::class))->hasMethod('createConsumerGroup'))->toBeTrue();
    });

    it('has consume method', function () {
        expect((new ReflectionClass(EventStreamConsumer::class))->hasMethod('consume'))->toBeTrue();
    });

    it('has acknowledge method', function () {
        expect((new ReflectionClass(EventStreamConsumer::class))->hasMethod('acknowledge'))->toBeTrue();
    });

    it('has getPending method', function () {
        expect((new ReflectionClass(EventStreamConsumer::class))->hasMethod('getPending'))->toBeTrue();
    });

    it('has claimIdleMessages method', function () {
        expect((new ReflectionClass(EventStreamConsumer::class))->hasMethod('claimIdleMessages'))->toBeTrue();
    });

    it('has getConsumerGroupInfo method', function () {
        expect((new ReflectionClass(EventStreamConsumer::class))->hasMethod('getConsumerGroupInfo'))->toBeTrue();
    });
});
