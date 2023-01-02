<?php

namespace App\Filter;

class UserFilters
{
    private ?string $fullName           = null;
    private ?string $firstName          = null;
    private ?string $lastName           = null;
    private ?string $email              = null;
    private ?bool   $belongsToGroup     = null;
    private ?array  $groups             = null;
    private ?array  $disallowedGroups   = null;

    public function __construct()
    {
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getBelongsToGroup(): ?bool
    {
        return $this->belongsToGroup;
    }

    public function setBelongsToGroup(?bool $belongsToGroup): void
    {
        $this->belongsToGroup = $belongsToGroup;
    }

    public function getGroups(): ?array
    {
        return $this->groups;
    }

    public function setGroups(?array $groups): void
    {
        $this->groups = $groups;
    }

    public function getDisallowedGroups(): ?array
    {
        return $this->disallowedGroups;
    }

    public function setDisallowedGroups(?array $disallowedGroups): void
    {
        $this->disallowedGroups = $disallowedGroups;
    }

}