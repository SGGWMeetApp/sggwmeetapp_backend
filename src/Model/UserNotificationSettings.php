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

}