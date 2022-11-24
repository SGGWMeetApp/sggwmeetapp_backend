<?php

namespace App\Filter;

class PlaceFilters
{
    private ?string $name = null;
    private ?array  $categoryCodes = null;

    public function __construct()
    {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getCategoryCodes(): ?array
    {
        return $this->categoryCodes;
    }

    public function setCategoryCodes(?array $categoryCodes): void
    {
        $this->categoryCodes = $categoryCodes;
    }

}