<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugins;

use App\Domain\Shared\Models\Plugin;
use RuntimeException;

class PluginSandbox
{
    /**
     * Check if a plugin has a specific permission.
     */
    public function hasPermission(Plugin $plugin, string $permission): bool
    {
        if (! config('plugins.sandbox.enabled', true)) {
            return true;
        }

        return $plugin->hasPermission($permission);
    }

    /**
     * Enforce that a plugin has the required permission.
     *
     * @throws RuntimeException
     */
    public function enforce(Plugin $plugin, string $permission): void
    {
        if (! $this->hasPermission($plugin, $permission)) {
            throw new RuntimeException(
                "Plugin {$plugin->getFullName()} does not have permission: {$permission}"
            );
        }
    }

    /**
     * Check if a plugin can access a specific resource.
     *
     * @param  array<string>  $requiredPermissions
     */
    public function canAccess(Plugin $plugin, array $requiredPermissions): bool
    {
        foreach ($requiredPermissions as $permission) {
            if (! $this->hasPermission($plugin, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the permissions that a plugin is missing.
     *
     * @param  array<string>  $requiredPermissions
     * @return array<string>
     */
    public function getMissingPermissions(Plugin $plugin, array $requiredPermissions): array
    {
        return array_filter(
            $requiredPermissions,
            fn ($p) => ! $this->hasPermission($plugin, $p),
        );
    }

    /**
     * Validate that a plugin only uses declared permissions.
     *
     * @return array{valid: bool, undeclared: array<string>}
     */
    public function validatePermissions(Plugin $plugin): array
    {
        $declared = $plugin->permissions ?? [];
        $validation = PluginPermissions::validate($declared);

        return [
            'valid'      => $validation['valid'],
            'undeclared' => $validation['invalid'],
        ];
    }
}
