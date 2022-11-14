<?php

namespace App\Model;

use App\Security\User;

class UserGroup {

    private ?int $groupId;
    private string $name;
    private User $owner;

    private array $users;
    private array $meetupEvents;

    /**
     * @param ?int $groupId
     * @param string $name
     * @param User $owner
     */
    public function __construct(?int $groupId, string $name, User $owner)
    {
        $this->groupId = $groupId;
        $this->name = $name;
        $this->owner = $owner;
        $this->addUser($owner);
        $this->meetupEvents = [];
    }

    /**
     * @return array
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * @param array $users
     */
    public function setUsers(array $users): void
    {
        $this->users = $users;
    }

    /**
     * @return array
     */
    public function getMeetupEvents(): array
    {
        return $this->meetupEvents;
    }

    /**
     * @param array $meetupEvents
     */
    public function setMeetupEvents(array $meetupEvents): void
    {
        $this->meetupEvents = $meetupEvents;
    }

    /**
     * @return int|null
     */
    public function getGroupId(): ?int
    {
        return $this->groupId;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param int|null $groupId
     */
    public function setGroupId(?int $groupId): void
    {
        $this->groupId = $groupId;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return User
     */
    public function getOwner(): User
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     */
    public function setOwner(User $owner): void
    {
        $this->owner = $owner;
    }

    public function addUser(User $user): void
    {
        $this->users[] = $user;
    }


}