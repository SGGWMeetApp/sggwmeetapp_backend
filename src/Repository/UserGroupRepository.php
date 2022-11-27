<?php

namespace App\Repository;

use App\Model\UserGroup;
use App\Security\User;
use App\Serializer\UserGroupNormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class UserGroupRepository extends BaseRepository implements UserGroupRepositoryInterface
{
    private Connection $connection;
    private UserGroupNormalizer $userGroupNormalizer;
    private string $tableName = 'app_owner.user_groups';
    private string $joinedTableName = 'app_owner.users_user_groups';

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection, UserGroupNormalizer $userGroupNormalizer)
    {
        $this->connection = $connection;
        $this->userGroupNormalizer = $userGroupNormalizer;
    }

    /**
     * @inheritDoc
     * @throws DriverException
     * @throws DbalException
     * @throws SerializerExceptionInterface
     * @throws UniqueConstraintViolationException
     */
    public function findOrFail(int $userGroupId): UserGroup
    {
        $sql = 'SELECT ug.group_id, ug.name, ug.owner_id,
       ( 
            SELECT to_json(array_agg(row_to_json(d)))
            FROM (
                SELECT 
                    u2.user_id,
                    u2.username, 
                    u2.first_name, 
                    u2.last_name,
                    u2.email,
                    u2.phone_number_prefix,
                    u2.phone_number,
                    u2.description
                FROM user_groups ug2
                JOIN users_user_groups uug2 on ug2.group_id = uug2.group_id
                JOIN users u2 on uug2.user_id = u2.user_id
                WHERE ug2.group_id = ug.group_id
            ) d 
        ) as users
        FROM user_groups ug
        JOIN users_user_groups uug on ug.group_id = uug.group_id
        JOIN users u on u.user_id = uug.user_id 
        WHERE ug.group_id = :group_id
        GROUP BY ug.group_id ';

        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('group_id', $userGroupId);
            $result = $statement->executeQuery();

            $data = $result->fetchAssociative();
            if(!$data) {
                throw new EntityNotFoundException();
            }
            return $this->userGroupNormalizer->denormalize($data, 'UserGroup');

        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }

    }

    /**
     * @throws SerializerExceptionInterface
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     */
    public function findAll(): array
    {
        $sql = 'SELECT ug.group_id, ug.name, ug.owner_id,
       (
            SELECT to_json(array_agg(row_to_json(d)))
            FROM (
                SELECT 
                    u2.user_id,
                    u2.username, 
                    u2.first_name, 
                    u2.last_name,
                    u2.email,
                    u2.phone_number_prefix,
                    u2.phone_number,
                    u2.description
                FROM user_groups ug2
                JOIN users_user_groups uug2 on ug2.group_id = uug2.group_id
                JOIN users u2 on uug2.user_id = u2.user_id
                WHERE ug2.group_id = ug.group_id 
            ) d 
        ) as users
        FROM user_groups ug
        JOIN users_user_groups uug on ug.group_id = uug.group_id
        JOIN users u on u.user_id = uug.user_id 
        GROUP BY ug.group_id ';

        try {
            $statement = $this->connection->prepare($sql);
            $result = $statement->executeQuery();

            $userGroups = [];
            while($data = $result->fetchAssociative()) {
                $userGroups [] = $this->userGroupNormalizer->denormalize($data, 'UserGroup');
            }
            return $userGroups;
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }

    }

    /**
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException|SerializerExceptionInterface
     */
    public function findAllGroupsForUser(int $userId): array
    {
        $sql = 'SELECT ug.group_id, ug.name, ug.owner_id,
        (
            SELECT to_json(array_agg(row_to_json(d)))
            FROM (
                SELECT 
                    u2.user_id,
                    u2.username, 
                    u2.first_name, 
                    u2.last_name,
                    u2.email,
                    u2.phone_number_prefix,
                    u2.phone_number,
                    u2.description
                FROM user_groups ug2
                JOIN users_user_groups uug2 on ug2.group_id = uug2.group_id
                JOIN users u2 on uug2.user_id = u2.user_id
                WHERE ug2.group_id = ug.group_id 
            ) d 
        ) as users
        FROM user_groups ug
        JOIN users_user_groups uug on ug.group_id = uug.group_id
        JOIN users u on u.user_id = uug.user_id 
        WHERE uug.user_id = :user_id
        GROUP BY ug.group_id ';

        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue("user_id", $userId);
            $result = $statement->executeQuery();

            $userGroups = [];
            while($data = $result->fetchAssociative()) {
                $userGroups [] = $this->userGroupNormalizer->denormalize($data, 'UserGroup');
            }

            return $userGroups;
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     */
    public function add(UserGroup $userGroup): void
    {
        $sql = 'INSERT INTO ' . $this->tableName .
            '(name, owner_id)
            VALUES(:name, :owner_id) 
            RETURNING group_id';

//        $sqlJoined = 'INSERT INTO ' . $this->joinedTableName .
//            '(user_id, group_id)
//            VALUES(:user_id, :group_id)';

        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('name', $userGroup->getName());
            $statement->bindValue('owner_id', $userGroup->getOwner()->getId(), ParameterType::INTEGER);
            $result = $statement->executeQuery();

            $groupId = $result->fetchAssociative()["group_id"];
            $userGroup->setGroupId($groupId);

            $this->addGroupUser($userGroup, $userGroup->getOwner());

//            $statement = $this->connection->prepare($sqlJoined);
//            $statement->bindValue('user_id', $userGroup->getOwner()->getId(), ParameterType::INTEGER);
//            $statement->bindValue('group_id', $userGroup->getGroupId());
//            $statement->executeQuery();

        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }

    }

    /**
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     */
    public function addGroupUser(UserGroup $userGroup, User $user): void
    {
        $sql = 'INSERT INTO ' . $this->joinedTableName .
            '(user_id, group_id)
            VALUES(:user_id, :group_id)';

        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('user_id', $user->getId(), ParameterType::INTEGER);
            $statement->bindValue('group_id', $userGroup->getGroupId());
            $statement->executeQuery();

        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    public function update(UserGroup $userGroup): void
    {
        // TODO: Implement update() method.
    }

    /**
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     */
    public function deleteUserFromGroup(int $userGroupId, int $userId): void
    {
        $sql = 'DELETE FROM ' . $this->joinedTableName . ' WHERE user_id = :user_id AND group_id = :group_id';

        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('user_id', $userId);
            $statement->bindValue('group_id', $userGroupId);
            $statement->executeQuery();

        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     */
    public function delete(UserGroup $userGroup): void
    {
        $sql = 'DELETE FROM ' . $this->tableName . ' WHERE group_id = :group_id';

        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('group_id', $userGroup->getGroupId());
            $statement->executeQuery();

        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }
}

