<?php

namespace App\Model;

class GeoLocation
{
    private float $latitude;
    private float $longitude;

    /**
     * @param float $longitude
     * @param float $latitude
     */
    public function __construct(float $latitude, float $longitude)
    {
        $this->setLatitude($latitude);
        $this->setLongitude($longitude);
    }

    /**
     * @return float
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * @param float $longitude
     */
    public function setLongitude(float $longitude): void
    {
        if ($longitude < -180.0 || $longitude > 180.0) {
            throw new \InvalidArgumentException("Invalid longitude. Longitude must be in range [-180, 180]");
        }
        $this->longitude = $longitude;
    }

    /**
     * @return float
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * @param float $latitude
     */
    public function setLatitude(float $latitude): void
    {
        if($latitude < -90.0 || $latitude > 90.0) {
            throw new \InvalidArgumentException("Invalid latitude. Latitude must be in range [-90, 90]");
        }
        $this->latitude = $latitude;
    }

}