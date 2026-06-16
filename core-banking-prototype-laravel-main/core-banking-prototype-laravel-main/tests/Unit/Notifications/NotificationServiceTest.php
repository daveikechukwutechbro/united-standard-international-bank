<?php

declare(strict_types=1);

use App\Domain\Shared\Notifications\NotificationService;

describe('NotificationService', function () {
    it('can be instantiated', function () {
        $service = new NotificationService();
        expect($service)->toBeInstanceOf(NotificationService::class);
    });

    it('registers and checks channels', function () {
        $service = new NotificationService();
        expect($service->hasChannel('email'))->toBeFalse();

        $service->registerChannel('email', function () {
        });
        expect($service->hasChannel('email'))->toBeTrue();
        expect($service->getRegisteredChannels())->toContain('email');
    });

    it('sends notification through registered channel', function () {
        $service = new NotificationService();
        $sent = false;

        $service->registerChannel('in_app', function () use (&$sent) {
            $sent = true;
        });

        $results = $service->send('user-1', 'test', ['message' => 'Hello'], ['in_app']);
        expect($results['in_app'])->toBe('sent');
        expect($sent)->toBeTrue();
    });

    it('returns no_handler for unregistered channel', function () {
        $service = new NotificationService();
        $results = $service->send('user-1', 'test', [], ['sms']);
        expect($results['sms'])->toBe('no_handler');
    });

    it('queues and flushes notifications', function () {
        $service = new NotificationService();
        $count = 0;

        $service->registerChannel('push', function () use (&$count) {
            $count++;
        });

        $service->queue('user-1', 'type-a', [], ['push']);
        $service->queue('user-2', 'type-b', [], ['push']);
        expect($service->getPendingCount())->toBe(2);

        $results = $service->flush();
        expect($results)->toHaveCount(2);
        expect($count)->toBe(2);
        expect($service->getPendingCount())->toBe(0);
    });

    it('returns available channels', function () {
        $service = new NotificationService();
        $channels = $service->getAvailableChannels();
        expect($channels)->toContain('email');
        expect($channels)->toContain('push');
        expect($channels)->toContain('in_app');
        expect($channels)->toContain('webhook');
        expect($channels)->toContain('sms');
    });

    it('returns event triggers', function () {
        $service = new NotificationService();
        $triggers = $service->getEventTriggers();
        expect($triggers)->toHaveKey('account.created');
        expect($triggers)->toHaveKey('payment.completed');
        expect($triggers)->toHaveKey('fraud.detected');
    });

    it('uses in_app as default channel', function () {
        $service = new NotificationService();
        $results = $service->send('user-1', 'test', []);
        expect($results)->toHaveKey('in_app');
    });
});
