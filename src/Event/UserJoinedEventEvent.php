<?php

namespace App\Event;

use App\Model\Event;
use App\Security\User;
use Symfony\Contracts\EventDispatcher\Event as SymfonyEvent;

class UserJoinedEventEvent extends SymfonyEvent
{
    public const NAME = 'user_event.joined';

    private User $user;
    private Event $event;

    /**
     * @param User $user
     * @param Event $event
     */
    public function __construct(User $user, Event $event)
    {
        $this->user = $user;
        $this->event = $event;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return Event
     */
    public function getEvent(): Event
    {
        return $this->event;
    }

}