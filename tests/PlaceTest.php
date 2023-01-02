<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Model\Place;
use App\Model\GeoLocation;

class PlaceTest extends TestCase
{
    public function testPlaceID(): void
    {
        $place = new Place(1,"Nazwa",new GeoLocation(18.2, 178.3),"Lokacja","Opis",0.1);
        $this->assertEquals($place->getId(),1);
        $this ->assertIsInt($place->getId());
    }

    public function testPlaceName(): void
    {
        $place = new Place(1,"Nazwa",new GeoLocation(18.2, 178.3),"Lokacja","Opis",0.1);
        $this->assertEquals($place->getName(),"Nazwa");
        $this ->assertIsString($place->getName());
    }

    public function testPlaceGeoLocation(): void
    {
        $place = new Place(1,"Nazwa",new GeoLocation(18.2, 178.3),"Lokacja","Opis",0.1);
        $this->assertEquals($place->getGeoLocation(),new GeoLocation(18.2, 178.3));
       
    }
    public function testPlaceTekstLocation(): void
    {
        $place = new Place(1,"Nazwa",new GeoLocation(18.2, 178.3),"Lokacja","Opis",0.1);
        $this->assertEquals($place->getTextLocation(),"Lokacja");
        $this ->assertIsString($place->getTextLocation());  
    }
    public function testPlaceDescription(): void
    {
        $place = new Place(1,"Nazwa",new GeoLocation(18.2, 178.3),"Lokacja","Opis",0.1);
        $this->assertEquals($place->getDescription(),"Opis");
        $this ->assertIsString($place->getDescription());  
    }
    public function testLatitude(): void
    {
        $place = new Place(1,"Nazwa",new GeoLocation(18.2, 178.3),"Lokacja","Opis",0.1);
        $this->assertEquals($place->getRatingPercent(),0.1);
        $this->assertIsFloat($place->getRatingPercent());
    }
    public function testCategoryCode(): void
    {
        $place = new Place(1,"Nazwa",new GeoLocation(18.2, 178.3),"Lokacja","Opis",0.1);
        
        $place -> addCategoryCode("RESTAURANT");
        $this->assertIsArray($place->getCategoryCodes());
    }
    public function testPhotoPaths(): void
    {
        $place = new Place(1,"Nazwa",new GeoLocation(18.2, 178.3),"Lokacja","Opis",0.1);
        
        $place -> addPhotoPath("photo path");
        $this->assertIsArray($place->getPhotoPaths());
    }
    public function testMenuPath(): void
    {
        $place = new Place(1,"Nazwa",new GeoLocation(18.2, 178.3),"Lokacja","Opis",0.1);
        
        $place -> setMenuPath('http://menu1.json');
        $this->assertEquals($place->getMenuPath(),'http://menu1.json');
        $this->assertIsString($place->getMenuPath());
    }
    
    



}
