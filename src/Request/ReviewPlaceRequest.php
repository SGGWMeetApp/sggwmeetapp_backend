<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ReviewPlaceRequest
{
    /**
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Type("string")
     * @Assert\Length(max="2000")
     * @var string
     */
    public $comment;

    /**
     * @Assert\NotNull()
     * @Assert\Type("boolean")
     * @var boolean
     */
    public $isPositive;
}