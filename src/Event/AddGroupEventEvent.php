<?php

namespace App\Event;

use App\Model\PrivateEvent;
use Symfony\Contracts\EventDispatcher\Event as SymfonyEvent;

class AddGroupEventEvent extends SymfonyEvent
{

    private PrivateEvent $privateEvent;

    public function __construct(PrivateEvent $privateEvent)
    {
        $this->privateEvent = $privateEvent;
    }

    public function getPrivateEvent(): PrivateEvent
    {
        return $this->privateEvent;
    }

}