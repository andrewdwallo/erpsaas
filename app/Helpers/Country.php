<?php

namespace App\Helpers;

use ArrayAccess;
use Illuminate\Support\Collection;

class Country implements ArrayAccess
{
    /**
     * The country data array.
     */
    protected array $data;

    /**
     * Create a new country instance.
     */
    public function __construct($data)
    {
        $this->setData($data);
    }

    /**
     * Set the country data array.
     */
    public function setData($data): static
    {
        if (is_array($data)) {
            $this->data = $data;
        } elseif ($data instanceof self) {
            $this->data = $data->getData();
        } else {
            $this->data = [];
        }

        return $this;
    }

    /**
     * Get the country data array.
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * Set a single country data array value.
     */
    public function set($key, $value): static
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Get a single country data array value.
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
     * Get the country id.
     */
    public function getId(): ?int
    {
        return $this->get('id');
    }

    /**
     * Get the country name.
     */
    public function getName(): ?string
    {
        return $this->get('name');
    }

    /**
     * Get the country iso2.
     */
    public function getIso2(): ?string
    {
        return $this->get('iso2');
    }

    /**
     * Get the country iso3.
     */
    public function getIso3(): ?string
    {
        return $this->get('iso3');
    }

    /**
     * Get the country numeric code.
     */
    public function getNumericCode(): ?string
    {
        return $this->get('numeric_code');
    }

    /**
     * Get the country phone code.
     */
    public function getPhoneCode(): ?string
    {
        return $this->get('phone_code');
    }

    /**
     * Get the country capital.
     */
    public function getCapital(): ?string
    {
        return $this->get('capital');
    }

    /**
     * Get the country currency code.
     */
    public function getCurrency(): ?string
    {
        return $this->get('currency');
    }

    /**
     * Get the country currency name.
     */
    public function getCurrencyName(): ?string
    {
        return $this->get('currency_name');
    }

    /**
     * Get the country currency symbol.
     */
    public function getCurrencySymbol(): ?string
    {
        return $this->get('currency_symbol');
    }

    /**
     * Get the country tld.
     */
    public function getTld(): ?string
    {
        return $this->get('tld');
    }

    /**
     * Get the country native name.
     */
    public function getNative(): ?string
    {
        return $this->get('native');
    }

    /**
     * Get the country region.
     */
    public function getRegion(): ?string
    {
        return $this->get('region');
    }

    /**
     * Get the country region id.
     */
    public function getRegionId(): ?int
    {
        return $this->get('region_id');
    }

    /**
     * Get the country subregion.
     */
    public function getSubregion(): ?string
    {
        return $this->get('subregion');
    }

    /**
     * Get the country subregion id.
     */
    public function getSubregionId(): ?int
    {
        return $this->get('subregion_id');
    }

    /**
     * Get the country nationality.
     */
    public function getNationality(): ?string
    {
        return $this->get('nationality');
    }

    /**
     * Get the country timezones.
     */
    public function getTimezones(): Collection
    {
        return collect($this->get('timezones') ?? []);
    }

    /**
     * Get the country translations.
     */
    public function getTranslations(): Collection
    {
        return collect($this->get('translations') ?? []);
    }

    /**
     * Get the country latitude.
     */
    public function getLatitude(): ?string
    {
        return $this->get('latitude');
    }

    /**
     * Get the country longitude.
     */
    public function getLongitude(): ?string
    {
        return $this->get('longitude');
    }

    /**
     * Get the country flag emoji.
     */
    public function getEmoji(): ?string
    {
        return $this->get('emoji');
    }

    /**
     * Get the country flag unicode.
     */
    public function getEmojiU(): ?string
    {
        return $this->get('emojiU');
    }

    /**
     * Get the country states.
     */
    public function getStates(): ?array
    {
        $countryCode = $this->getIso2();

        return LocationDataLoader::getAllStates($countryCode, false)->all();
    }
}
