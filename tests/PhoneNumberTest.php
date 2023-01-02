<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Model\PhoneNumber;

class PhoneNumberTest extends TestCase
{
    public function testPrefix(): void
    {
        $phone = new PhoneNumber("+48","11111111");
        $this->assertEquals($phone->getPrefix(),"+48");
        $this->assertIsString($phone->getPrefix());

    }
    public function testNumber(): void
    {
        $phone = new PhoneNumber("+48","11111111");
        $this->assertEquals($phone->getNumber(),"11111111");
        $this->assertIsString($phone->getNumber());

    }
}
