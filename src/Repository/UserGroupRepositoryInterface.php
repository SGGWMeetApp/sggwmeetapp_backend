<?php

namespace App\Repository;

use App\Model\UserGroup;
use App\Security\User;

interface UserGroupRepositoryInterface
{
    /**
     * @throws EntityNotFoundException
     */
    public function findOrFail(int $userGroupId): UserGroup;

    public function findAll(): array;

    public function findAllGroupsForUser(int $userId): array;

    public function add(UserGroup $userGroup): void;

    public function addGroupUser(UserGroup $userGroup, User $user): void;

    public function update(UserGroup $userGroup): void;

    public function deleteUserFromGroup(int $userGroupId, int $userId): void;

    public function delete(UserGroup $userGroup): void;

}
