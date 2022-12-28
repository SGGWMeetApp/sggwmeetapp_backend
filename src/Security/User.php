<?php

namespace App\Security;

use App\Model\AccountData;
use App\Model\NotificationSetting;
use App\Model\UserData;
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
    private ?int $id;
    private UserData $userData;
    private AccountData $accountData;
    private array $userGroups;
    private UserNotificationSettings $notificationSettings;
    private \DateTimeInterface $registrationDate;

    public function __construct($id, $userData, $accountData, $registrationDate, $userGroups=[])
    {
        $this->id = $id;
        $this->userData = $userData;
        $this->accountData = $accountData;
        $this->registrationDate = $registrationDate;
        $this->userGroups = $userGroups;
        $this->notificationSettings = new UserNotificationSettings();
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
     * @return UserData
     */
    public function getUserData(): UserData
    {
        return $this->userData;
    }

    /**
     * @return AccountData
     */
    public function getAccountData(): AccountData
    {
        return $this->accountData;
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

    /**
     * @inheritDoc
     */
    public function getRoles(): array
    {
        return $this->getAccountData()->getRoles();
    }

    /**
     * @inheritDoc
     */
    public function getPassword(): string
    {
        return $this->getAccountData()->getPassword();
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
        return $this->getAccountData()->getEmail();
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
            return $this->getAccountData()->getEmail();
        }
        return null;
    }

    public function getRegistrationDate(): \DateTimeInterface
    {
        return $this->registrationDate;
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