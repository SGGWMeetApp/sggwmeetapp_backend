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

    /**
     * @throws EntityNotFoundException
     * @param string $userId
     * @return User
     */
    public function findByIdOrFail(string $userId): User;

    public function add(User $user): void;
}