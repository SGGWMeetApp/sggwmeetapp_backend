<?php

namespace App\Repository;

use App\Model\UserGroup;
use App\Security\User;
use App\Serializer\UserGroupNormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class UserGroupRepository extends BaseRepository implements UserGroupRepositoryInterface
{
    private Connection $connection;
    private UserGroupNormalizer $userGroupNormalizer;
    private string $tableName = 'app_owner.user_groups';
    private string $joinedTableName = 'app_owner.users_user_groups';

    /**
     * @param Connection $connection
     * @param UserGroupNormalizer $userGroupNormalizer
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
        $queryBuilder = $this->createFindQueryBuilder($this->connection);
        $queryBuilder->andWhere('ug.group_id = :groupId');
        $queryBuilder->groupBy('ug.group_id');
        $queryBuilder->setParameter('groupId', $userGroupId);
        try {
            $result = $queryBuilder->executeQuery();
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
        $queryBuilder = $this->createFindQueryBuilder($this->connection);
        $queryBuilder->groupBy('ug.group_id');
        try {
            $result = $queryBuilder->executeQuery();
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
        $queryBuilder = $this->createFindQueryBuilder($this->connection);
        $queryBuilder->andWhere('uug.user_id = :userId');
        $queryBuilder->groupBy('ug.group_id');
        $queryBuilder->setParameter('userId', $userId);

        try {
            $result = $queryBuilder->executeQuery();
            $userGroups = [];
            while($data = $result->fetchAssociative()) {
                $userGroups [] = $this->userGroupNormalizer->denormalize($data, 'UserGroup');
            }

            return $userGroups;
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    private function createFindQueryBuilder(Connection $connection): QueryBuilder
    {
        $queryBuilder = new QueryBuilder($connection);
        $queryBuilder
            ->select('ug.group_id, ug.name, ug.owner_id,
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
                            u2.description,
                            u2.creation_date,
                            u2.event_notification,
                            u2.group_add_notification,
                            u2.group_remove_notification
                        FROM user_groups ug2
                        JOIN users_user_groups uug2 on ug2.group_id = uug2.group_id
                        JOIN users u2 on uug2.user_id = u2.user_id
                        WHERE ug2.group_id = ug.group_id 
                    ) d 
                ) AS users,
                (SELECT COUNT(event_id) FROM app_owner.events ev WHERE ev.group_id = ug.group_id AND start_date > CURRENT_TIMESTAMP) AS incoming_events_count
            ')
            ->from($this->tableName, 'ug')
            ->join('ug', 'app_owner.users_user_groups', 'uug', 'ug.group_id = uug.group_id')
            ->join('uug', 'app_owner.users', 'u',  'u.user_id = uug.user_id');

        return $queryBuilder;
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

        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('name', $userGroup->getName());
            $statement->bindValue('owner_id', $userGroup->getOwner()->getId(), ParameterType::INTEGER);
            $result = $statement->executeQuery();

            $groupId = $result->fetchAssociative()["group_id"];
            $userGroup->setGroupId($groupId);

            $this->addGroupUser($userGroup, $userGroup->getOwner());

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

    /**
     * @throws DbalException\DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     */
    public function update(UserGroup $userGroup): void
    {
        $sql = 'UPDATE ' . $this->tableName .
            ' SET name = :name, owner_id = :ownerId WHERE group_id = :groupId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('name', $userGroup->getName());
            $statement->bindValue('ownerId', $userGroup->getOwner()->getId(), ParameterType::INTEGER);
            $statement->bindValue('groupId', $userGroup->getGroupId());
            $statement->executeQuery();
        } catch (DbalException\DriverException $e) {
            $this->handleDriverException($e);
        }
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

