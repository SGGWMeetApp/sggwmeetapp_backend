<?php

namespace App\Model;

class PhoneNumber
{
    private string $prefix;
    private string $number;

    /**
     * @param string $phonePrefix
     * @param string $phoneNumber
     */
    public function __construct(string $phonePrefix, string $phoneNumber)
    {
        $this->prefix = $phonePrefix;
        $this->number = $phoneNumber;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param string $phonePrefix
     */
    public function setPrefix(string $phonePrefix): void
    {
        $this->prefix = $phonePrefix;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @param string $phoneNumber
     */
    public function setNumber(string $phoneNumber): void
    {
        $this->number = $phoneNumber;
    }

}