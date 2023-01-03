<?php

namespace App\Repository;

use App\Filter\UserFilters;
use App\Model\NotificationSetting;
use App\Model\UserNotificationSettings;
use App\Security\User;
use App\Serializer\UserNormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    private Connection $connection;
    private string $tableName = 'app_owner.users';
    private UserNormalizer $userNormalizer;

    public function __construct(Connection $connection, UserNormalizer $userNormalizer)
    {
        $this->connection = $connection;
        $this->userNormalizer = $userNormalizer;
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
     * @throws UniqueConstraintViolationException
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws SerializerExceptionInterface
     */
    public function findAll(UserFilters $filters): array
    {
        $queryBuilder = new QueryBuilder($this->connection);
        $queryBuilder->select('u.*')->from($this->tableName, 'u');
        $this->addUserFiltersToQuery($queryBuilder, $filters);
        try {
            $result = $queryBuilder->executeQuery();
            $users = [];
            while ($data = $result->fetchAssociative()) {
                $users [] = $this->userNormalizer->denormalize($data, User::class);
            }
            return $users;
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    private function addUserFiltersToQuery(QueryBuilder $queryBuilder, UserFilters $filters): void
    {
        if($filters->getFullName() !== null) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->like('(LOWER(u.first_name) || LOWER(u.last_name))', ':fullName') . ' OR ' .
                $queryBuilder->expr()->like('(LOWER(u.last_name) || LOWER(u.first_name))', ':fullName')
            );
            $queryBuilder->setParameter('fullName', '%' . strtolower(
                    str_replace(' ', '', $filters->getFullName())
                ) . '%'
            );
        }
        if ($filters->getFirstName() !== null) {
            $queryBuilder->andWhere($queryBuilder->expr()->like('LOWER(u.first_name)', ':firstName'));
            $queryBuilder->setParameter('firstName', '%' . strtolower($filters->getFirstName()) . '%');
        }
        if ($filters->getLastName() !== null) {
            $queryBuilder->andWhere($queryBuilder->expr()->like('LOWER(u.last_name)', ':lastName'));
            $queryBuilder->setParameter('lastName', '%' . strtolower($filters->getLastName()) . '%');
        }
        if ($filters->getEmail() !== null) {
            $queryBuilder->andWhere($queryBuilder->expr()->like('LOWER(u.email)', ':email'));
            $queryBuilder->setParameter('email', '%' . strtolower($filters->getEmail())) . '%';
        }
        if ($filters->getBelongsToGroup() !== null) {
            if($filters->getBelongsToGroup()) {
                $queryBuilder->andWhere("u.user_id IN (SELECT DISTINCT(uug.user_id) FROM app_owner.users_user_groups uug");
            } else {
                $queryBuilder->andWhere("u.user_id NOT IN (SELECT DISTINCT(uug.user_id) FROM app_owner.users_user_groups uug");
            }
        }
        if($filters->getGroups() !== null) {
            $queryBuilder->andWhere(
                sprintf('
                u.user_id IN (
                    SELECT DISTINCT(uug1.user_id)
                    FROM app_owner.users_user_groups uug1
                    WHERE uug1.user_id = u.user_id AND uug1.group_id IN (%s)
                )',
                implode(',', $filters->getGroups())
            ));
        }
        if($filters->getDisallowedGroups() !== null) {
            $queryBuilder->andWhere(
                sprintf('
                u.user_id NOT IN (
                    SELECT DISTINCT(uug2.user_id)
                    FROM app_owner.users_user_groups uug2
                    WHERE uug2.user_id = u.user_id AND uug2.group_id IN (%s)
                )',
                    implode(',', $filters->getDisallowedGroups())
                ));
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
        (:username, :email, :password, :firstName, :lastName, :phonePrefix, :phone, :description) RETURNING user_id";
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('username', $user->getUserIdentifier());
            $statement->bindValue('email', $user->getUserIdentifier());
            $statement->bindValue('password', $user->getPassword());
            $statement->bindValue('firstName', $user->getUserData()->getFirstName());
            $statement->bindValue('lastName', $user->getUserData()->getLastName());
            $statement->bindValue('phonePrefix', $user->getUserData()->getPhoneNumber()->getPrefix());
            $statement->bindValue('phone', $user->getUserData()->getPhoneNumber()->getNumber());
            $statement->bindValue('description', $user->getUserData()->getDescription());
            $result = $statement->executeQuery();
            $data = $result->fetchAssociative();
            if ($data) {
                $user->setId($data['user_id']);
            }
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws UniqueConstraintViolationException
     * @throws DbalException
     */
    public function update(User $user): void
    {
        $sql = 'UPDATE ' . $this->tableName . ' SET
            first_name = :firstName,
            last_name = :lastName,
            phone_number_prefix = :phonePrefix,
            phone_number = :phoneNumber,
            description = :description,
            avatar_path = :avatarPath
        WHERE user_id = :userId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('firstName', $user->getUserData()->getFirstName());
            $statement->bindValue('lastName', $user->getUserData()->getLastName());
            $statement->bindValue('phonePrefix', $user->getUserData()->getPhoneNumber()->getPrefix());
            $statement->bindValue('phoneNumber', $user->getUserData()->getPhoneNumber()->getNumber());
            $statement->bindValue('description', $user->getUserData()->getDescription());
            $statement->bindValue('avatarPath', $user->getUserData()->getAvatarUrl());
            $statement->bindValue('userId', $user->getId());
            $statement->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @throws DriverException
     * @throws UniqueConstraintViolationException
     * @throws EntityNotFoundException
     * @throws DbalException
     */
    public function updateUserNotificationSettings(User $user, UserNotificationSettings $userNotificationSettings): void
    {
        $queryBuilder = new QueryBuilder($this->connection);
        $queryBuilder->update($this->tableName);
        /** @var NotificationSetting $setting */
        foreach ($userNotificationSettings->getSettings() as $setting) {
            $queryBuilder
                ->set($setting->getName(), ':'.$setting->getName())
                ->setParameter($setting->getName(), $setting->isEnabled(),ParameterType::BOOLEAN);
        }
        try {
            $queryBuilder->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @throws DriverException
     * @throws UniqueConstraintViolationException
     * @throws EntityNotFoundException
     * @throws DbalException
     */
    public function updateUserPassword(User $user, string $passwordHash): void
    {
        $sql = 'UPDATE '.$this->tableName.' SET password = :password WHERE user_id = :user_id';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('password', $passwordHash);
            $statement->bindValue('user_id', $user->getId());
            $statement->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }


}