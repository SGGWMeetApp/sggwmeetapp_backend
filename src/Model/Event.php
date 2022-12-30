<?php

namespace App\Model;

use App\Security\User;

abstract class Event
{
    private ?int $id;
    private string $name;
    private ?string $description;
    private Place $location;
    private \DateTimeInterface $startDate;
    private User $author;
    private bool $canEdit;
    private bool $notificationsEnabled;
    private int  $attendersCount = 0;

    /**
     * @param int|null $id
     * @param string $name
     * @param Place $location
     * @param string|null $description
     * @param \DateTimeInterface $startDate
     * @param User $author
     * @param bool $canEdit
     * @param bool $notificationsEnabled
     */
    public function __construct(
        ?int                $id,
        string              $name,
        Place               $location,
        ?string             $description,
        \DateTimeInterface  $startDate,
        User                $author,
        bool                $canEdit,
        bool                $notificationsEnabled
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->location = $location;
        $this->description = $description;
        $this->startDate = $startDate;
        $this->author = $author;
        $this->canEdit = $canEdit;
        $this->notificationsEnabled = $notificationsEnabled;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }


    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLocation(): Place
    {
        return $this->location;
    }

    public function setLocation(Place $location): void
    {
        $this->location = $location;
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

    /**
     * @return bool
     */
    public function isNotificationsEnabled(): bool
    {
        return $this->notificationsEnabled;
    }

    /**
     * @param bool $notificationsEnabled
     */
    public function setNotificationsEnabled(bool $notificationsEnabled): void
    {
        $this->notificationsEnabled = $notificationsEnabled;
    }

    /**
     * @return int
     */
    public function getAttendersCount(): int
    {
        return $this->attendersCount;
    }

    public function setAttendersCount(int $attendersCount): void
    {
        $this->attendersCount = $attendersCount;
    }

    public function isEqualTo(Event $event): bool
    {
        if ($this->getId() != $event->getId()) {
            return false;
        }
        return true;
    }

}