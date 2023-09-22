<?php

namespace App\Helpers;

use ArrayAccess;

class State implements ArrayAccess
{
    /**
     * The state data array.
     */
    protected array $data;

    /**
     * Create a new state instance.
     */
    public function __construct($data)
    {
        $this->setData($data);
    }

    /**
     * Set the state data array.
     */
    public function setData($data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the state data array.
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * Set a single state data array value.
     */
    public function set($key, $value): static
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Get a single state data array value.
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

    public function getId(): ?int
    {
        return $this->get('id');
    }

    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function getStateCode(): ?string
    {
        return $this->get('state_code');
    }

    public function getLatitude(): ?string
    {
        return $this->get('latitude');
    }

    public function getLongitude(): ?string
    {
        return $this->get('longitude');
    }

    public function getType(): ?string
    {
        return $this->get('type');
    }

    public function getCities(): ?array
    {
        $countryCode = $this->get('country_code');
        $stateCode = $this->get('state_code');

        return LocationDataLoader::getAllCities($countryCode, $stateCode, false)->all();
    }
}
