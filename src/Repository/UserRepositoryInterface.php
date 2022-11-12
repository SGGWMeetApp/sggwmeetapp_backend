<?php

namespace App\Repository;

use App\Security\User;

interface UserRepositoryInterface
{
    /**
     * @throws EntityNotFoundException
     * @param string $identifier
     * @return User
     */
    public function findOrFail(string $identifier): User;

    public function add(User $user): void;
}