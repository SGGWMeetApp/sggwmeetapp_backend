<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class GetUsersEligibleForGroupRequest
{

    /**
     * @Assert\NotBlank(allowNull=true)
     * @var string
     */
    public $namePhrase;
}