<?php

namespace App\Response;

use App\Model\UserGroup;
use App\Security\User;
use App\Serializer\UserGroupNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;

class GroupUsersResponse extends JsonResponse
{
    /**
     * GroupUsersResponse constructor
     * @param UserGroup $userGroup
     * @param User $user
     */
    public function __construct(UserGroup $userGroup, User $user)
    {
        parent::__construct($this->responseData($userGroup, $user));
    }

    public function responseData(UserGroup $userGroup, User $user): array
    {
        $userGroupNormalizer = new UserGroupNormalizer();
        $isUserAdmin = $user->isEqualTo($userGroup->getOwner());
        $userGroupData = $userGroupNormalizer->normalize($userGroup);
        unset($userGroupData["adminData"]);
        unset($userGroupData["memberCount"]);
        unset($userGroupData["incomingEventsCount"]);
        return [
            ...$userGroupData,
            "isUserAdmin" => $isUserAdmin,
        ];
    }

}