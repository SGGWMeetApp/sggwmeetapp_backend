<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserNotificationsRequest
{
    /**
     * @Assert\Type(type="boolean")
     */
    public $eventNotification;

    /**
     * @Assert\Type(type="boolean")
     */
    public $groupAddNotification;

    /**
     * @Assert\Type(type="boolean")
     */
    public $groupRemoveNotification;
}