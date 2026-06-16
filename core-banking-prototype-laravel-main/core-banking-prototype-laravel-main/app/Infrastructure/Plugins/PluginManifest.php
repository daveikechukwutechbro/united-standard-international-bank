<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugins;

use InvalidArgumentException;
use JsonException;
use RuntimeException;

class PluginManifest
{
    public readonly string $vendor;

    public readonly string $name;

    public readonly string $version;

    public readonly string $displayName;

    public readonly string $description;

    public readonly string $author;

    public readonly string $license;

    public readonly string $homepage;

    public readonly string $entryPoint;

    /** @var array<string> */
    public readonly array $permissions;

    /** @var array<string, string> */
    public readonly array $dependencies;

    /** @var array<string, mixed> */
    public readonly array $extra;

    /**
     * @param  array<string, mixed>  $data
     */
    private function __construct(array $data)
    {
        $this->vendor = $data['vendor'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->version = $data['version'] ?? '0.0.0';
        $this->displayName = $data['display_name'] ?? $this->name;
        $this->description = $data['description'] ?? '';
        $this->author = $data['author'] ?? '';
        $this->license = $data['license'] ?? 'MIT';
        $this->homepage = $data['homepage'] ?? '';
        $this->entryPoint = $data['entry_point'] ?? 'ServiceProvider';
        $this->permissions = $data['permissions'] ?? [];
        $this->dependencies = $data['dependencies'] ?? [];
        $this->extra = $data['extra'] ?? [];
    }

    public static function fromFile(string $path): self
    {
        if (! file_exists($path)) {
            throw new InvalidArgumentException("Plugin manifest not found: {$path}");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Failed to read plugin manifest: {$path}");
        }

        try {
            $data = json_decode($content, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException("Invalid plugin manifest JSON: {$path} — {$e->getMessage()}");
        }

        if (! is_array($data)) {
            throw new RuntimeException("Invalid plugin manifest JSON: {$path}");
        }

        return new self($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function getFullName(): string
    {
        return "{$this->vendor}/{$this->name}";
    }

    public function validate(): bool
    {
        $namePattern = '/^[a-zA-Z0-9_-]+$/';

        return ! empty($this->vendor)
            && ! empty($this->name)
            && ! empty($this->version)
            && preg_match($namePattern, $this->vendor) === 1
            && preg_match($namePattern, $this->name) === 1
            && preg_match('/^\d+\.\d+\.\d+/', $this->version) === 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'vendor'       => $this->vendor,
            'name'         => $this->name,
            'version'      => $this->version,
            'display_name' => $this->displayName,
            'description'  => $this->description,
            'author'       => $this->author,
            'license'      => $this->license,
            'homepage'     => $this->homepage,
            'entry_point'  => $this->entryPoint,
            'permissions'  => $this->permissions,
            'dependencies' => $this->dependencies,
            'extra'        => $this->extra,
        ];
    }
}
