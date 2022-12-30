<?php

namespace App\Repository;

use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface as BaseResetPasswordRequestRepositoryInterface;

interface ResetPasswordRequestRepositoryInterface extends BaseResetPasswordRequestRepositoryInterface
{
    public function deleteLastRequestForUser(object $user): void;
}