<?php

namespace App\Security\Provider;

use App\Repository\EntityNotFoundException;
use App\Repository\UserRepositoryInterface;
use App\Security\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @method UserInterface loadUserByIdentifier(string $identifier)
 */
class DatabaseUserProvider implements UserProviderInterface
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @inheritDoc
     */
    public function loadUserByUsername(string $username): User
    {
        return $this->getUser($username);
    }

    private function getUser(string $username): User
    {
        try {
            return $this->userRepository->findOrFail($username);
        } catch (EntityNotFoundException $e) {
            $exception = new UserNotFoundException(sprintf('Username "%s" not found in the database.', $username));
            $exception->setUserIdentifier($username);
            throw $exception;
        }
    }

    /**
     * @inheritDoc
     */
    public function refreshUser(UserInterface $user): User
    {
        if (!$user instanceof User)
        {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->getUser($user->getUserIdentifier());
    }

    /**
     * @inheritDoc
     */
    public function supportsClass(string $class): bool
    {
        return 'Security\User' === $class;
    }

    public function __call(string $name, array $arguments)
    {
        if ($name == 'loadUserByIdentifier') {
            return $this->getUser($arguments[0]);
        }
        return null;
    }
}