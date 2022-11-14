<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateUserGroupRequest
{
    /**
     * @Assert\Type(type="string")
     * @var string
     */
    public $name;
}