<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class PlaceFiltersRequest
{
    /**
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type(type="string")
     */
    public $name;

    /**
     * @Assert\All(
     *     @Assert\NotBlank(),
     *     @Assert\Type(type="string")
     * )
     */
    public $categoryCodes;
}