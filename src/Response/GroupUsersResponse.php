<?php

namespace App\Response;

use App\Factory\NormalizerFactory;
use App\Model\UserGroup;
use App\Security\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class GroupUsersResponse extends JsonResponse
{
    private NormalizerFactory $normalizerFactory;

    /**
     * GroupUsersResponse constructor
     * @param UserGroup $userGroup
     * @param User $user
     * @param NormalizerFactory $normalizerFactory
     * @throws SerializerExceptionInterface
     */
    public function __construct(UserGroup $userGroup, User $user, NormalizerFactory $normalizerFactory)
    {
        $this->normalizerFactory = $normalizerFactory;
        parent::__construct($this->responseData($userGroup, $user));
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function responseData(UserGroup $userGroup, User $user): array
    {
        $isUserAdmin = $user->isEqualTo($userGroup->getOwner());
        $userGroupData = $this->normalizerFactory->getNormalizer($userGroup)->normalize($userGroup);
        unset($userGroupData["adminData"]);
        unset($userGroupData["memberCount"]);
        unset($userGroupData["incomingEventsCount"]);
        return [
            ...$userGroupData,
            "isUserAdmin" => $isUserAdmin,
        ];
    }

}