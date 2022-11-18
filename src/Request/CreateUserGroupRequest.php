<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateUserGroupRequest
{
    /**
     * @Assert\NotBlank()
     * @Assert\Type(type="string")
     * @Assert\Length(min=1, max=255)
     * @var string
     */
    public $name;
}