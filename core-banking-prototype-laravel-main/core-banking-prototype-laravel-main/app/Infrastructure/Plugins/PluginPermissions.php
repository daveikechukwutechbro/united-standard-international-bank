<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugins;

class PluginPermissions
{
    public const DATABASE_READ = 'database:read';

    public const DATABASE_WRITE = 'database:write';

    public const API_INTERNAL = 'api:internal';

    public const API_EXTERNAL = 'api:external';

    public const EVENTS_LISTEN = 'events:listen';

    public const EVENTS_DISPATCH = 'events:dispatch';

    public const QUEUE_DISPATCH = 'queue:dispatch';

    public const CACHE_READ = 'cache:read';

    public const CACHE_WRITE = 'cache:write';

    public const FILESYSTEM_READ = 'filesystem:read';

    public const FILESYSTEM_WRITE = 'filesystem:write';

    public const CONFIG_READ = 'config:read';

    /**
     * @var array<string, string>
     */
    private static array $descriptions = [
        'database:read'    => 'Read access to database tables',
        'database:write'   => 'Write access to database tables',
        'api:internal'     => 'Access to internal API endpoints',
        'api:external'     => 'Make outbound HTTP requests',
        'events:listen'    => 'Listen to application events',
        'events:dispatch'  => 'Dispatch application events',
        'queue:dispatch'   => 'Dispatch jobs to queues',
        'cache:read'       => 'Read from application cache',
        'cache:write'      => 'Write to application cache',
        'filesystem:read'  => 'Read files from storage',
        'filesystem:write' => 'Write files to storage',
        'config:read'      => 'Read application configuration',
    ];

    /**
     * @var array<string, string>
     */
    private static array $categories = [
        'database:read'    => 'Data Access',
        'database:write'   => 'Data Access',
        'api:internal'     => 'API',
        'api:external'     => 'API',
        'events:listen'    => 'Events',
        'events:dispatch'  => 'Events',
        'queue:dispatch'   => 'Queue',
        'cache:read'       => 'Cache',
        'cache:write'      => 'Cache',
        'filesystem:read'  => 'Storage',
        'filesystem:write' => 'Storage',
        'config:read'      => 'Configuration',
    ];

    /**
     * Get all available permissions.
     *
     * @return array<string>
     */
    public static function all(): array
    {
        return array_keys(self::$descriptions);
    }

    /**
     * Check if a permission string is valid.
     */
    public static function isValid(string $permission): bool
    {
        return isset(self::$descriptions[$permission]);
    }

    /**
     * Get the description for a permission.
     */
    public static function describe(string $permission): string
    {
        return self::$descriptions[$permission] ?? 'Unknown permission';
    }

    /**
     * Get the category for a permission.
     */
    public static function category(string $permission): string
    {
        return self::$categories[$permission] ?? 'Other';
    }

    /**
     * Validate a list of requested permissions.
     *
     * @param  array<string>  $permissions
     * @return array{valid: bool, invalid: array<string>}
     */
    public static function validate(array $permissions): array
    {
        $invalid = array_filter($permissions, fn ($p) => ! self::isValid($p));

        return [
            'valid'   => empty($invalid),
            'invalid' => array_values($invalid),
        ];
    }

    /**
     * Get permissions grouped by category.
     *
     * @return array<string, array<string>>
     */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (self::$categories as $permission => $category) {
            $grouped[$category][] = $permission;
        }

        return $grouped;
    }
}
