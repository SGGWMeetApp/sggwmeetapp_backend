<?php

namespace App\Model;

use App\Security\User;

class UserGroup {

    private ?int $groupId;
    private string $name;
    private ?User $owner;

    private array $users = [];
    private int $memberCount;

    private array $events = [];
    private int $incomingEventsCount = 0;

    /**
     * @param ?int $groupId
     * @param string $name
     * @param ?User $owner
     * @param int $memberCount
     */
    public function __construct(?int $groupId, string $name, ?User $owner, int $memberCount = 0)
    {
        $this->groupId = $groupId;
        $this->name = $name;
        $this->owner = $owner;
        $this->memberCount = $memberCount;
    }

    /**
     * @return int
     */
    public function getIncomingEventsCount(): int
    {
        return $this->incomingEventsCount;
    }

    /**
     * @param int $incomingEventsCount
     */
    public function setIncomingEventsCount(int $incomingEventsCount): void
    {
        $this->incomingEventsCount = $incomingEventsCount;
    }

    /**
     * @return int
     */
    public function getMemberCount(): int
    {
        return $this->memberCount;
    }

    /**
     * @param int $memberCount
     */
    public function setMemberCount(int $memberCount): void
    {
        $this->memberCount = $memberCount;
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
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @param array $events
     */
    public function setEvents(array $events): void
    {
        $this->events = $events;
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

    /**
     * @param User $user
     */
    public function addUser(User $user): void
    {
        $this->users[] = $user;
    }

    /**
     * @param PrivateEvent $event
     */
    public function addEvent(PrivateEvent $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function containsUser(User $user): bool
    {
        foreach($this->users as $groupUser) {
            if($user->isEqualTo($groupUser)) {
                return true;
            }
        }

        return false;
    }

}