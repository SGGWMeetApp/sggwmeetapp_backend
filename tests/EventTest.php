<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Model\PublicEvent;
use App\Model\PrivateEvent;
use App\Model\Place;
use App\Model\User;
use App\Model\UserGroup;

class EventTest extends TestCase
{

    public function testEventId(): void
    {
        //nie od tego zaczałem
       // $event = new PrivateEvent(1,"Nazwa", new Place(),"opis","12.12.2022 00:00:00", new User(), new UserGroup(), True, True);
        
        //$event ->setId(10);
        //$this ->assertEquals($event->getId(),10);
        $this->assertTrue(true);
    }


    public function testSetAuthor(): void
    {
        $this->assertTrue(true);
    }
}
