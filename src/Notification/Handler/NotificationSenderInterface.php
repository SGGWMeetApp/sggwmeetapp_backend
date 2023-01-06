<?php

namespace App\Notification\Handler;

interface NotificationSenderInterface
{
    public function sendNotifications(array $eventAttenders): bool;
}
