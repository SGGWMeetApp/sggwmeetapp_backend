<?php

namespace App\Model;

use App\Security\User;

class PrivateEvent extends Event
{
    private ?UserGroup $userGroup;
    private bool $notificationsEnabled;

    public function __construct(?int $id, string $name, Place $location, ?string $description, \DateTimeInterface $startDate, User $author, ?UserGroup $userGroup, bool $canEdit=true) {
        parent::__construct($id, $name, $location, $description, $startDate, $author, $canEdit);
        $this->userGroup = $userGroup;
        $this->notificationsEnabled = true;
    }

    /**
     * @return UserGroup
     */
    public function getUserGroup(): UserGroup
    {
        return $this->userGroup;
    }

    /**
     * @param UserGroup $userGroup
     */
    public function setUserGroup(UserGroup $userGroup): void
    {
        $this->userGroup = $userGroup;
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

    public function isEqualTo(PrivateEvent $privateEvent): bool
    {
        if ($this->getId() != $privateEvent->getId()) {
            return false;
        }
        return true;
    }

}
