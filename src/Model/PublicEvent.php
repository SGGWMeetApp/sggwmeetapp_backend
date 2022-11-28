<?php

namespace App\Model;

use App\Security\User;

class PublicEvent extends Event
{

    /**
     * @param int|null $id
     * @param string $name
     * @param Place $location
     * @param string|null $description
     * @param \DateTimeInterface $startDate
     * @param User $author
     * @param bool $canEdit
     */
    public function __construct(
        ?int                $id,
        string              $name,
        Place               $location,
        ?string             $description,
        \DateTimeInterface  $startDate,
        User                $author,
        bool                $canEdit=true
    )
    {
        parent::__construct($id, $name, $location, $description, $startDate, $author, $canEdit);
    }

    public function convertToPrivateEvent(UserGroup $userGroup): PrivateEvent
    {
        return new PrivateEvent(
            $this->getId(),
            $this->getName(),
            $this->getLocation(),
            $this->getDescription(),
            $this->getStartDate(),
            $this->getAuthor(),
            $userGroup,
            $this->getCanEdit(),
            true
        );
    }

}