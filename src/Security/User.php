<?php

namespace App\Security;

use App\Model\UserGroup;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method string getUserIdentifier()
 */
class User implements UserInterface, EquatableInterface, PasswordAuthenticatedUserInterface
{
    private ?int    $id;
    private string  $firstName;
    private string  $lastName;
    private string  $email;
    private string  $password;
    private array   $roles;
    private string  $phonePrefix;
    private string  $phone;
    private ?string $description;
    private array $userGroups;

    public function __construct($id, $firstName, $lastName, $email, $password, $phonePrefix, $phone, $description, $roles, $userGroups=[])
    {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->password = $password;
        $this->roles = $roles;
        $this->phonePrefix = $phonePrefix;
        $this->phone = $phone;
        $this->description = $description;
        $this->userGroups[] = $userGroups;
    }

    /**
     * @return array
     */
    public function getUserGroups(): array
    {
        return $this->userGroups;
    }

    /**
     * @param array $userGroups
     */
    public function setUserGroups(array $userGroups): void
    {
        $this->userGroups = $userGroups;
    }

    /**
     * @param UserGroup $userGroup
     */
    public function addGroup(UserGroup $userGroup): void
    {
        $this->userGroups[] = $userGroup;
    }

    /**
     * @return int|null
     */
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

    /**
     * @inheritDoc
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @inheritDoc
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @inheritDoc
     */
    public function getSalt(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
        // There is no need for erasing credentials in this app
    }

    /**
     * @inheritDoc
     */
    public function getUsername(): string
    {
        return $this->email;
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof User) {
            return false;
        }
        if ($this->getUserIdentifier() != $user->getUserIdentifier()) {
            return false;
        }
        return true;
    }

    public function __call(string $name, array $arguments)
    {
        if ($name == 'getUserIdentifier') {
            return $this->email;
        }
        return null;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhonePrefix(): string
    {
        return $this->phonePrefix;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

}