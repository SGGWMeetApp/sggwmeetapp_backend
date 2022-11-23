<?php

namespace App\Repository;

use App\Filter\UserFilters;
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

    public function findAll(UserFilters $filters): array;

    public function add(User $user): void;

    public function update(User $user): void;
}