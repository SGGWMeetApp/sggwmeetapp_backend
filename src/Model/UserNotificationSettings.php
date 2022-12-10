<?php

namespace App\Model;

class UserNotificationSettings
{
    private array $settings;

    public function __construct()
    {
        $this->settings = [];
    }

    public function addSetting(NotificationSetting $notificationSetting): void
    {
        $this->settings [] = $notificationSetting;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getEnabledSettings(): array
    {
        $enabledSettings = [];
        foreach($this->settings as $setting) {
            if ($setting->isEnabled()) {
                $enabledSettings [] = $setting;
            }
        }
        return $enabledSettings;
    }

    public function getSettingByName(string $name): NotificationSetting {
        foreach($this->settings as $setting) {
            if ($setting->getName() === $name) return $setting;
        }
        throw new \OutOfBoundsException('Notification '.$name.' does not exist in user notification settings.');
    }

}