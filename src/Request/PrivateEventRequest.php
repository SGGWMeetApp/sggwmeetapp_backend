<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class PrivateEventRequest
{

    /**
     * @Assert\Type("integer")
     * @var integer
     */
    public $publicEventId;

    /**
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Assert\Length(min=1, max=255)
     * @var string
     */
    public $name;

    /**
     * @Assert\NotNull()
     * @Assert\Type("integer")
     * @var integer
     */
    public $locationId;

    /**
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("string")
     * @Assert\Length(max=2000)
     * @var string
     */
    public $description;

    /**
     * @Assert\NotNull()
     * @Assert\Type("\DateTime")
     */
    public $startDate;
}