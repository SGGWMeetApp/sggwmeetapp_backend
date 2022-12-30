<?php

namespace App\Service\SecurityHelper;

use App\Repository\EntityNotFoundException;
use App\Repository\UserRepositoryInterface;
use App\Security\User;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;

class JWTIdentityHelper
{
    private User $user;

    /**
     * @param Security $security
     * @param UserRepositoryInterface $userRepository
     * @throws EntityNotFoundException
     * @throws AuthenticationException
     */
    public function __construct(Security $security, UserRepositoryInterface $userRepository)
    {
        if($security->getToken() == null) {
            throw new AuthenticationException('User not authenticated.');
        }
        $jwtUser = $security->getToken()->getUser();
        $this->user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

}