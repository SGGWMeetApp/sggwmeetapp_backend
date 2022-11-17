<?php

namespace App\Response;

use App\Model\UserGroup;
use App\Security\User;
use App\Serializer\UserGroupNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserGroupResponse extends JsonResponse
{
    /**
     * UserGroupResponse constructor
     * @param UserGroup $userGroup
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
        return [
            ...$userGroupData,
            "isUserAdmin" => $isUserAdmin
        ];
    }

}