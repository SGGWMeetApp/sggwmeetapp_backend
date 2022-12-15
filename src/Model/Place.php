<?php

namespace App\Model;

class Place
{
    private ?int $id;
    private string $name;
    private ?string $description;
    private GeoLocation $geoLocation;
    private string $textLocation;
    private ?float $ratingPercent;
    private array $categoryCodes;
    private array $photoPaths;
    private int $reviewsCount = 0;

    /**
     * @param int|null $id
     * @param string $name
     * @param GeoLocation $geoLocation
     * @param string $textLocation
     * @param string|null $description
     * @param float|null $ratingPercent
     */
    public function __construct(?int $id, string $name, GeoLocation $geoLocation, string $textLocation, ?string $description, ?float $ratingPercent)
    {
        $this->id = $id;
        $this->name = $name;
        $this->geoLocation = $geoLocation;
        $this->textLocation = $textLocation;
        $this->description = $description;
        $this->ratingPercent = $ratingPercent;
        $this->categoryCodes = [];
        $this->photoPaths = [];
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

    public function getTextLocation(): string
    {
        return $this->textLocation;
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
        } else {
            throw new \InvalidArgumentException("Code $upperCode already exists in this place's category codes.");
        }
    }

    /**
     * @return array
     */
    public function getPhotoPaths(): array
    {
        return $this->photoPaths;
    }

    public function addPhotoPath(string $photoPath): void
    {
        if(!in_array($photoPath, $this->photoPaths)) {
            $this->photoPaths [] = $photoPath;
        } else {
            throw new \InvalidArgumentException("$photoPath photo path already exists in this place's photo paths.");
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