<?php

namespace App\Repository;

use App\Security\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\DriverException;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     */
    public function findOrFail(string $identifier): User
    {
        $sql = "SELECT * FROM app_owner.users WHERE username = :username";
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('username', $identifier);
            $result = $statement->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
        $data = $result->fetchAssociative();
        if ($data !== false) {
            // TODO: Replace password hash and roles in below statement with db values
            return new User(
                $data['user_id'],
                $data['first_name'],
                $data['last_name'],
                $identifier,
                '$2y$13$ZGORNacvE1FwJxId3hNKIe5jlx1GR57antmza.kRr9lmmYQ6gc3J2',
                ['ROLE_USER']
            );
        } else {
            throw new EntityNotFoundException();
        }
    }
}