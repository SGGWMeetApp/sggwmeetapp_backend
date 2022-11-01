<?php

namespace App\Security\Provider;

use App\Security\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @method UserInterface loadUserByIdentifier(string $identifier)
 */
class DatabaseUserProvider implements UserProviderInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @inheritDoc
     */
    public function loadUserByUsername(string $username): User
    {
        return $this->getUser($username);
    }

    /**
     * @throws DbalException
     */
    private function getUser(string $username): User
    {
        $sql = "SELECT * FROM app_owner.users WHERE username = :username";
        $statement = $this->connection->prepare($sql);
        $statement->bindValue('username', $username);
        $result = $statement->executeQuery();
        $row = $result->fetchAssociative();
        $exception = new UserNotFoundException(sprintf('Username "%s" not found in the database.', $username));
        $exception->setUserIdentifier($username);
        if ($row !== false) {
            if (!$row['username']) {
                throw $exception;
            } else {
                //TODO: Replace fixed password value with password hash from db
                return new User(
                    $row['first_name'],
                    $row['last_name'],
                    $username,
                    '$2y$13$ZGORNacvE1FwJxId3hNKIe5jlx1GR57antmza.kRr9lmmYQ6gc3J2',
                    ['ROLE_USER']
                );
            }
        }
        throw $exception;
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