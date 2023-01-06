<?php

namespace App\Model;

class UserNotificationSettings
{
    public const EVENT_NOTIFICATION = 'event_notification';
    public const GROUP_ADD_NOTIFICATION = 'group_add_notification';
    public const GROUP_REMOVE_NOTIFICATION = 'group_remove_notification';

    public const NOTIFICATION_NAMES = [
        self::EVENT_NOTIFICATION,
        self::GROUP_ADD_NOTIFICATION,
        self::GROUP_REMOVE_NOTIFICATION
    ];

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