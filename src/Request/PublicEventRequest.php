<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class PublicEventRequest
{
    /**
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("string")
     * @Assert\Length(max="2000")
     * @var string
     */
    public $name;

     /**
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("string")
     * @Assert\Length(max="2000")
     * @var string
     */
    public $locationId;

     /**
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("int")
     * @Assert\Length(max="2000")
     * @var string
     */
    public $locationName;

     /**
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("string")
     * @Assert\Length(max="2000")
     * @var string
     */
    public $description;

    /**
     * @Assert\NotNull()
     * @Assert\Type("\DateTime")
     */
    public $startDate;
}