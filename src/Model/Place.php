<?php

namespace App\Model;

class Place
{
    private ?int $id;
    private string $name;
    private ?string $description;
    private GeoLocation $geoLocation;
    private ?float $ratingPercent;
    private array $categoryCodes;
    private int $reviewsCount = 0;

    /**
     * @param int|null $id
     * @param string $name
     * @param GeoLocation $geoLocation
     * @param string|null $description
     * @param float|null $ratingPercent
     */
    public function __construct(?int $id, string $name, GeoLocation $geoLocation, ?string $description, ?float $ratingPercent)
    {
        $this->id = $id;
        $this->name = $name;
        $this->geoLocation = $geoLocation;
        $this->description = $description;
        $this->ratingPercent = $ratingPercent;
        $this->categoryCodes = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGeoLocation(): GeoLocation
    {
        return $this->geoLocation;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getRatingPercent(): ?float
    {
        return $this->ratingPercent;
    }

    public function getCategoryCodes(): array
    {
        return $this->categoryCodes;
    }

    public function addCategoryCode(string $code): void
    {
        $upperCode = strtoupper($code);
        if(!in_array($upperCode, $this->categoryCodes)) {
            $this->categoryCodes [] = $upperCode;
        }
    }

    /**
     * @return int
     */
    public function getReviewsCount(): int
    {
        return $this->reviewsCount;
    }

    /**
     * @param int $reviewsCount
     */
    public function setReviewsCount(int $reviewsCount): void
    {
        $this->reviewsCount = $reviewsCount;
    }

}