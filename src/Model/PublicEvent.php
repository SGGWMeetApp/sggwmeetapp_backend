<?php

namespace App\Model;
namespace App\Security;

class PublicEvent
{
    private ?int $id;
    private string $name;
    private ?string $description;
    private GeoLocation $geoLocation;
    private data $startDate;
    private User $author;
    private bool $canEdit;

    /**
     * @param int|null $id
     * @param string $name
     * @param GeoLocation $geoLocation
     * @param string|null $description
     * @param data $startDate
     * @param User $author
     * @param bool $canEdit
     */
    public function __construct(?int $id, string $name, GeoLocation $geoLocation, ?string $description, data $startDate, User $author, bool $canEdit=true)
    {
        $this->id = $id;
        $this->name = $name;
        $this->geoLocation = $geoLocation;
        $this->description = $description;
        $this->startDate = $startDate;
        $this->author = $author;
        $this->canEdit =$canEdit;
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

    public function getStartDate(): date
    {
        return $this->startDate;
    }

    public function setStartDate(date $startDate): void
    {
        $this->startDate =$startDate;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): void
    {
        $this->author =$author;
    }

    public function getCanEdit(): bool
    {
        return $this->canEdit;
    }

   



    

}