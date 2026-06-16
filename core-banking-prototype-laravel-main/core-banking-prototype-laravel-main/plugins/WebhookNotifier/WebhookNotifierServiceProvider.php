<?php

declare(strict_types=1);

namespace Plugins\WebhookNotifier;

use App\Infrastructure\Plugins\Contracts\PluginHookInterface;
use App\Infrastructure\Plugins\PluginHookManager;
use Illuminate\Support\ServiceProvider;

class WebhookNotifierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /** @var PluginHookManager $hookManager */
        $hookManager = $this->app->make(PluginHookManager::class);

        $listener = new WebhookHookListener(
            webhookUrl: config('plugins.webhook-notifier.webhook_url', ''),
            secret: config('plugins.webhook-notifier.secret'),
            events: config('plugins.webhook-notifier.events', []),
        );

        $hooks = ['account.created', 'payment.completed', 'compliance.alert', 'wallet.transfer'];

        foreach ($hooks as $hook) {
            $hookManager->register(new class($hook, $listener) implements PluginHookInterface {
                public function __construct(
                    private readonly string $hookName,
                    private readonly WebhookHookListener $listener
                ) {}

                public function getHookName(): string
                {
                    return $this->hookName;
                }

                /**
                 * @param  array<string, mixed>  $payload
                 */
                public function handle(array $payload): void
                {
                    $this->listener->handle($this->hookName, $payload);
                }

                public function getPriority(): int
                {
                    return 100;
                }
            });
        }
    }
}
