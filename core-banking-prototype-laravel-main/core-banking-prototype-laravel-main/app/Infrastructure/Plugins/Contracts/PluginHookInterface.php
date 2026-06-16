<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugins\Contracts;

interface PluginHookInterface
{
    /**
     * Get the hook name this listener responds to.
     */
    public function getHookName(): string;

    /**
     * Handle the hook event.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): void;

    /**
     * Get the priority for this hook listener (lower = earlier).
     */
    public function getPriority(): int;
}
