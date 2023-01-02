<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Model\GeoLocation;

class GeoLocationTest extends TestCase
{
    public function testLongitude(): void
    {
        $geo = new GeoLocation(18.2, 178.3);
        $this->assertEquals($geo->getLongitude(),178.3);
        $this->assertIsFloat($geo->getLongitude());
    }

    public function testLatitude(): void
    {
        $geo = new GeoLocation(18.2, 178.3);
        $this->assertEquals($geo->getLatitude(),18.2);
        $this->assertIsFloat($geo->getLatitude());
    }
}
