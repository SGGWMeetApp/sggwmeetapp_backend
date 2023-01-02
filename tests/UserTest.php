<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Model\UserData;
use App\Model\PhoneNumber;

class UserTest extends TestCase
{
    public function testFirstName(): void
    {
        $user = new UserData("Imie", "Nazwisko", "Opis", new PhoneNumber("+48","11111111"));
        $this->assertTrue(true);
    }
}
