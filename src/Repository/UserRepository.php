<?php

namespace App\Repository;

use App\Security\User;
use App\Serializer\UserNormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    private Connection $connection;
    private string $tableName = 'app_owner.users';
    private UserNormalizer $userNormalizer;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->userNormalizer = new UserNormalizer();
    }

    /**
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     * @throws SerializerExceptionInterface
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
            return $this->userNormalizer->denormalize($data, User::class);
        } else {
            throw new EntityNotFoundException();
        }
    }

    /**
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     * @throws SerializerExceptionInterface
     */
    public function findByIdOrFail(string $userId): User
    {
        $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE user_id = :userId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue("userId", $userId);
            $result = $statement->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
        $data = $result->fetchAssociative();
        if ($data !== false) {
            return $this->userNormalizer->denormalize($data, User::class);
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
        " (username, email, password, first_name, last_name, phone_number_prefix, phone_number, description)
        VALUES
        (:username, :email, :password, :firstName, :lastName, :phonePrefix, :phone, :description)";
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('username', $user->getUserIdentifier());
            $statement->bindValue('email', $user->getUserIdentifier());
            $statement->bindValue('password', $user->getPassword());
            $statement->bindValue('firstName', $user->getFirstName());
            $statement->bindValue('lastName', $user->getLastName());
            $statement->bindValue('phonePrefix', $user->getPhonePrefix());
            $statement->bindValue('phone', $user->getPhone());
            $statement->bindValue('description', $user->getDescription());
            $statement->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }
}