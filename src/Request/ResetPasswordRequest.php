<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ResetPasswordRequest
{
    /**
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    public $email;
}