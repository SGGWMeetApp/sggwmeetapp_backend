<?php

namespace App\Model;

use App\Security\User;

class PrivateEvent extends Event
{
    private ?UserGroup $userGroup;

    public function __construct(?int $id, string $name, Place $location, ?string $description, \DateTimeInterface $startDate, User $author, ?UserGroup $userGroup, bool $canEdit=true) {
        parent::__construct($id, $name, $location, $description, $startDate, $author, $canEdit);
        $this->userGroup = $userGroup;
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




}
