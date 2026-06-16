<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugins;

use App\Infrastructure\Plugins\Contracts\PluginHookInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class PluginHookManager
{
    /**
     * Registered hook listeners grouped by hook name.
     *
     * @var array<string, array<int, PluginHookInterface>>
     */
    private array $hooks = [];

    /**
     * Available hook points in the system.
     *
     * @var array<string, string>
     */
    public const HOOK_POINTS = [
        'account.created'      => 'Fired when a new account is created',
        'account.updated'      => 'Fired when an account is updated',
        'payment.initiated'    => 'Fired when a payment is initiated',
        'payment.completed'    => 'Fired when a payment is completed',
        'payment.failed'       => 'Fired when a payment fails',
        'compliance.alert'     => 'Fired when a compliance alert is triggered',
        'compliance.kyc'       => 'Fired when KYC verification status changes',
        'wallet.transfer'      => 'Fired when a wallet transfer occurs',
        'wallet.created'       => 'Fired when a new wallet is created',
        'order.placed'         => 'Fired when a new exchange order is placed',
        'order.matched'        => 'Fired when an order is matched',
        'loan.applied'         => 'Fired when a loan application is submitted',
        'loan.approved'        => 'Fired when a loan is approved',
        'bridge.initiated'     => 'Fired when a cross-chain bridge transfer starts',
        'bridge.completed'     => 'Fired when a bridge transfer completes',
        'defi.position.opened' => 'Fired when a DeFi position is opened',
        'defi.position.closed' => 'Fired when a DeFi position is closed',
    ];

    public function __construct(
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Register a hook listener.
     */
    public function register(PluginHookInterface $listener): void
    {
        $hookName = $listener->getHookName();

        if (! isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }

        $this->hooks[$hookName][] = $listener;

        // Sort by priority
        usort(
            $this->hooks[$hookName],
            fn (PluginHookInterface $a, PluginHookInterface $b) => $a->getPriority() <=> $b->getPriority()
        );

        $this->logger?->debug("Plugin hook registered: {$hookName}", [
            'listener' => $listener::class,
            'priority' => $listener->getPriority(),
        ]);
    }

    /**
     * Dispatch a hook event to all registered listeners.
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(string $hookName, array $payload = []): void
    {
        $listeners = $this->hooks[$hookName] ?? [];

        if (empty($listeners)) {
            return;
        }

        $this->logger?->debug("Dispatching hook: {$hookName}", [
            'listener_count' => count($listeners),
            'payload_keys'   => array_keys($payload),
        ]);

        foreach ($listeners as $listener) {
            try {
                $listener->handle($payload);
            } catch (Throwable $e) {
                $this->logger?->error("Plugin hook listener failed: {$hookName}", [
                    'listener'  => $listener::class,
                    'error'     => $e->getMessage(),
                    'exception' => $e::class,
                ]);
            }
        }
    }

    /**
     * Unregister all listeners for a hook.
     */
    public function unregister(string $hookName): void
    {
        unset($this->hooks[$hookName]);
    }

    /**
     * Unregister a specific listener.
     */
    public function unregisterListener(string $hookName, string $listenerClass): void
    {
        if (! isset($this->hooks[$hookName])) {
            return;
        }

        $this->hooks[$hookName] = array_values(
            array_filter(
                $this->hooks[$hookName],
                fn (PluginHookInterface $listener) => $listener::class !== $listenerClass
            )
        );
    }

    /**
     * Get all registered hooks.
     *
     * @return array<string, array<int, string>>
     */
    public function getRegisteredHooks(): array
    {
        $result = [];

        foreach ($this->hooks as $hookName => $listeners) {
            $result[$hookName] = array_map(
                fn (PluginHookInterface $listener) => $listener::class,
                $listeners
            );
        }

        return $result;
    }

    /**
     * Get available hook points.
     *
     * @return array<string, string>
     */
    public function getAvailableHookPoints(): array
    {
        return self::HOOK_POINTS;
    }

    /**
     * Check if a hook has any registered listeners.
     */
    public function hasListeners(string $hookName): bool
    {
        return ! empty($this->hooks[$hookName]);
    }

    /**
     * Get listener count for a hook.
     */
    public function getListenerCount(string $hookName): int
    {
        return count($this->hooks[$hookName] ?? []);
    }
}
