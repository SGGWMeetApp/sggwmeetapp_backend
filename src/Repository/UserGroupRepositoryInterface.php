<?php

namespace App\Repository;

use App\Model\UserGroup;

interface UserGroupRepositoryInterface
{
    /**
     * @throws EntityNotFoundException
     */
    public function findOrFail(int $userGroupId): UserGroup;

    public function findAll(): array;

    public function add(UserGroup $userGroup): void;

    public function update(UserGroup $userGroup): void;

    public function delete(UserGroup $userGroup): void;

}
