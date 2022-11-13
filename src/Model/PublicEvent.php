<?php

namespace App\Model;

use App\Security\User;

class PublicEvent
{
    private ?int $id;
    private string $name;
    private ?string $description;
    private string $locationDataID;
    private \DateTimeInterface $startDate;
    private User $author;
    private bool $canEdit;

    /**
     * @param int|null $id
     * @param string $name
     * @param string $locationDataID
     * @param string|null $description
     * @param \DateTimeInterface $startDate
     * @param User $author
     * @param bool $canEdit
     */
    public function __construct(?int $id, string $name, string $locationData, ?string $description, \DateTimeInterface $startDate, User $author, bool $canEdit=true)
    {
        $this->id = $id;
        $this->name = $name;
        $this->locationData = $locationData;
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
    public function getLocationID(): string
    {
        return $this->locationData;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): void
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