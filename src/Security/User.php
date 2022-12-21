<?php

namespace App\Security;

use App\Model\NotificationSetting;
use App\Model\UserGroup;
use App\Model\UserNotificationSettings;
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
    private ?string $avatarUrl = null;
    private UserNotificationSettings $notificationSettings;
    private \DateTimeInterface $registrationDate;

    public function __construct($id, $firstName, $lastName, $email, $password, $phonePrefix, $phone, $description, $registrationDate, $roles, $userGroups=[])
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
        $this->registrationDate = $registrationDate;
        $this->userGroups = $userGroups;
        $this->notificationSettings = new UserNotificationSettings();
    }

    public function getUserGroups(): array
    {
        return $this->userGroups;
    }

    public function setUserGroups(array $userGroups): void
    {
        $this->userGroups = $userGroups;
    }

    public function addGroup(UserGroup $userGroup): void
    {
        $this->userGroups[] = $userGroup;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

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

    public function setPhonePrefix(string $phonePrefix): void
    {
        $this->phonePrefix = $phonePrefix;
    }


    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getRegistrationDate(): \DateTimeInterface
    {
        return $this->registrationDate;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): void
    {
        $this->avatarUrl = $avatarUrl;
    }

    public function getNotificationSettings(): UserNotificationSettings
    {
        return $this->notificationSettings;
    }

    public function setNotificationSettings(array $newSettings): UserNotificationSettings
    {
        $allSettings = $this->notificationSettings->getSettings();
        /** @var NotificationSetting $setting */
        foreach ($allSettings as $setting) {
            if (array_key_exists($setting->getName(), $newSettings)) {
                $setting->setEnabled($newSettings[$setting->getName()]);
            }
        }
        return $this->notificationSettings;
    }
}