<?php

namespace App\Helpers;

use ArrayAccess;

class City implements ArrayAccess
{
    protected array $data;

    public function __construct($data)
    {
        $this->setData($data);
    }

    /**
     * Set the city data array.
     */
    public function setData($data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the city data array.
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * Set a single city data array value.
     */
    public function set($key, $value): static
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Get a single city data array value.
     */
    public function get($key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Check if an offset exists in the data array.
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * Get an offset from the data array.
     */
    public function offsetGet($offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    /**
     * Set an offset in the data array.
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * Unset an offset in the data array.
     */
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * Get the city ID.
     */
    public function getId(): ?int
    {
        return $this->get('id');
    }

    /**
     * Get the city name.
     */
    public function getName(): ?string
    {
        return $this->get('name');
    }

    /**
     * Get the city latitude.
     */
    public function getLatitude(): ?string
    {
        return $this->get('latitude');
    }

    /**
     * Get the city longitude.
     */
    public function getLongitude(): ?string
    {
        return $this->get('longitude');
    }
}
