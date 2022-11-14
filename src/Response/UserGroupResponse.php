<?php

namespace App\Response;

use App\Model\UserGroup;
use App\Serializer\UserGroupNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserGroupResponse extends JsonResponse
{
    /**
     * UserGroupResponse constructor
     * @param UserGroup $userGroup
     */
    public function __construct(UserGroup $userGroup)
    {
        parent::__construct($this->responseData($userGroup));
    }

    public function responseData(UserGroup $userGroup): array
    {
        $userGroupNormalizer = new UserGroupNormalizer();
        return $userGroupNormalizer->normalize($userGroup);
    }

}