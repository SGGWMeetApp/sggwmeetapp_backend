<?php

namespace App\Response;

use App\Factory\NormalizerFactory;
use App\Model\UserGroup;
use App\Security\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class GroupsResponse extends JsonResponse
{
    private NormalizerFactory $normalizerFactory;

    /**
     * GroupsResponse constructor
     * @param UserGroup[] $userGroups
     * @param User $user
     * @param NormalizerFactory $normalizerFactory
     * @throws ExceptionInterface
     */
    public function __construct(array $userGroups, User $user, NormalizerFactory $normalizerFactory)
    {
        $this->normalizerFactory = $normalizerFactory;
        parent::__construct($this->responseData($userGroups, $user));
    }

    /**
     * @throws ExceptionInterface
     */
    public function responseData(array $userGroups, User $user): array
    {
        $normalizedUserGroups = [];
        foreach($userGroups as $userGroup) {
            $isUserAdmin = $user->isEqualTo($userGroup->getOwner());
            $normalizedUserGroup = $this->normalizerFactory->getNormalizer($userGroup)->normalize($userGroup);
            $normalizedUserGroup["adminData"]["isUserAdmin"] = $isUserAdmin;
            unset($normalizedUserGroup["users"]);
            $normalizedUserGroups [] = $normalizedUserGroup;
        }

        return ["groups" => $normalizedUserGroups];
    }



}