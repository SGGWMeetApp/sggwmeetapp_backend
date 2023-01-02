<?php

namespace App\Request;

use App\Constraint\UserPassword;

class ChangePasswordRequest
{
    /**
     * @UserPassword()
     */
    public $oldPassword;

    /**
     * @UserPassword()
     */
    public $newPassword;
}