<?php

namespace App\Response;

use App\Model\UserGroup;
use App\Security\User;
use App\Serializer\UserGroupNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class GroupsResponse extends JsonResponse
{
    /**
     * GroupsResponse constructor
     * @param UserGroup[] $userGroups
     * @param User $user
     */
    public function __construct(array $userGroups, User $user)
    {
        parent::__construct($this->responseData($userGroups, $user));
    }

    public function responseData(array $userGroups, User $user): array
    {
        $userGroupNormalizer = new UserGroupNormalizer();
        $normalizedUserGroups = [];
        foreach($userGroups as $userGroup) {
            $isUserAdmin = $user->isEqualTo($userGroup->getOwner());
            $normalizedUserGroup = $userGroupNormalizer->normalize($userGroup);
            $normalizedUserGroup["adminData"]["isUserAdmin"] = $isUserAdmin;
            unset($normalizedUserGroup["users"]);
            $normalizedUserGroups [] = $normalizedUserGroup;
        }

        return ["groups" => $normalizedUserGroups];
    }



}