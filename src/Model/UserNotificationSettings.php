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

    public function getSettingByName(string $name): NotificationSetting
    {
        /** @var NotificationSetting $setting */
        foreach($this->settings as $setting) {
            if($setting->getName() == $name) {
                return $setting;
            }
        }
        throw new \OutOfBoundsException('Setting '.$name.' does not exist in UserNotificationSettings.');
    }

}