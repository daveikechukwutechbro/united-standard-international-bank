<?php

declare(strict_types=1);

use App\Infrastructure\Plugins\Contracts\PluginHookInterface;
use App\Infrastructure\Plugins\PluginHookManager;

describe('PluginHookManager', function () {
    it('can register and dispatch hooks', function () {
        $manager = new PluginHookManager();
        $handled = false;

        $listener = new class ($handled) implements PluginHookInterface {
            public function __construct(private bool &$handled)
            {
            }

            public function getHookName(): string
            {
                return 'account.created';
            }

            public function handle(array $payload): void
            {
                $this->handled = true;
            }

            public function getPriority(): int
            {
                return 10;
            }
        };

        $manager->register($listener);
        expect($manager->hasListeners('account.created'))->toBeTrue();
        expect($manager->getListenerCount('account.created'))->toBe(1);

        $manager->dispatch('account.created', ['id' => 1]);
        expect($handled)->toBeTrue();
    });

    it('returns available hook points', function () {
        $manager = new PluginHookManager();
        $hookPoints = $manager->getAvailableHookPoints();

        expect($hookPoints)->toHaveKey('account.created');
        expect($hookPoints)->toHaveKey('payment.completed');
        expect($hookPoints)->toHaveKey('compliance.alert');
        expect($hookPoints)->toHaveKey('wallet.transfer');
    });

    it('dispatches nothing for unregistered hooks', function () {
        $manager = new PluginHookManager();
        // Should not throw
        $manager->dispatch('nonexistent.hook', ['data' => 'test']);

        expect($manager->hasListeners('nonexistent.hook'))->toBeFalse();
    });

    it('can unregister hooks', function () {
        $manager = new PluginHookManager();

        $listener = new class () implements PluginHookInterface {
            public function getHookName(): string
            {
                return 'wallet.transfer';
            }

            public function handle(array $payload): void
            {
            }

            public function getPriority(): int
            {
                return 10;
            }
        };

        $manager->register($listener);
        expect($manager->hasListeners('wallet.transfer'))->toBeTrue();

        $manager->unregister('wallet.transfer');
        expect($manager->hasListeners('wallet.transfer'))->toBeFalse();
    });

    it('returns registered hooks summary', function () {
        $manager = new PluginHookManager();

        $listener = new class () implements PluginHookInterface {
            public function getHookName(): string
            {
                return 'payment.completed';
            }

            public function handle(array $payload): void
            {
            }

            public function getPriority(): int
            {
                return 10;
            }
        };

        $manager->register($listener);
        $hooks = $manager->getRegisteredHooks();

        expect($hooks)->toHaveKey('payment.completed');
        expect($hooks['payment.completed'])->toHaveCount(1);
    });
});
