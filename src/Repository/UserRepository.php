<?php

namespace App\Repository;

use App\Security\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\DriverException;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    private Connection $connection;
    private string $tableName = 'app_owner.users';

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     */
    public function findOrFail(string $identifier): User
    {
        $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE email = :username';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue("username", $identifier);
            $result = $statement->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
        $data = $result->fetchAssociative();
        if ($data !== false) {
            return new User(
                $data['user_id'],
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['password'],
                $data['phone_number_prefix'],
                $data['phone_number'],
                $data['description'],
                ['ROLE_USER']
            );
        } else {
            throw new EntityNotFoundException();
        }
    }

    /**
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     */
    public function add(User $user): void
    {
        $sql = "INSERT INTO " . $this->tableName .
        " (username, email, password, first_name, last_name, phone_number_prefix, phone_number, location_sharing_mode, description)
        VALUES
        (:username, :email, :password, :firstName, :lastName, :phonePrefix, :phone, :locationSharingMode, :description)";
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('username', $user->getUserIdentifier());
            $statement->bindValue('email', $user->getUserIdentifier());
            $statement->bindValue('password', $user->getPassword());
            $statement->bindValue('firstName', $user->getFirstName());
            $statement->bindValue('lastName', $user->getLastName());
            $statement->bindValue('phonePrefix', $user->getPhonePrefix());
            $statement->bindValue('phone', $user->getPhone());
            $statement->bindValue('locationSharingMode', 1);
            $statement->bindValue('description', $user->getDescription());
            $statement->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }
}