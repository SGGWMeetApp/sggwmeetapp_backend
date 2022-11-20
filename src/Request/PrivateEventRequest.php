<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class PrivateEventRequest
{

    /**
     * @Assert\Type("string")
     * @var string
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
     * @Assert\Type("string")
     * @var string
     */
    public $locationId;

    /**
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("string")
     * @var string
     */
    public $description;

    /**
     * @Assert\NotNull()
     * @Assert\Type("\DateTime")
     */
    public $startDate;
}