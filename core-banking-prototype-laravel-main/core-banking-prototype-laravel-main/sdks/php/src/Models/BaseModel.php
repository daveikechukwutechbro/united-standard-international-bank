<?php

namespace FinAegis\Models;

abstract class BaseModel
{
    protected array $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Get attribute value.
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Set attribute value.
     *
     * @param  mixed  $value
     */
    public function __set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Check if attribute exists.
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Get all attributes.
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Convert to JSON.
     */
    public function toJson(): string
    {
        return json_encode($this->attributes);
    }
}
