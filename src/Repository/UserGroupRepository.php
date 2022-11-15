<?php

namespace App\Repository;

use App\Model\UserGroup;
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
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->userGroupNormalizer = new UserGroupNormalizer();
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
        // TODO: finish select with users and events
        $sql = '
            SELECT
                ug.group_id,
                ug.name,
                ug.owner_id
            FROM ' . $this->tableName .
            ' ug WHERE group_id = :userGroupId';

        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('userGroupId', $userGroupId);
            $result = $statement->executeQuery();
            if ($data = $result->fetchAssociative()) {
                return $this->userGroupNormalizer->denormalize($data, 'UserGroup');
            }
            throw new EntityNotFoundException();
        } catch (DbalException\DriverException $e) {
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
        $sql = '
            SELECT 
                ug.group_id,
                ug.name,
                (SELECT COUNT(uug.user_id) FROM app_owner.users_user_groups uug WHERE ug.group_id = uug.group_id) AS member_count,
                ARRAY_TO_JSON(ARRAY(
                    SELECT
                        u.first_name
--                    u.last_name
                FROM app_owner.users u
                WHERE u.user_id = ug.owner_id)) AS admin_data
            FROM ' . $this->tableName . ' ug';
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
     * @throws UniqueConstraintViolationException
     */
    public function add(UserGroup $userGroup): void
    {
        $sql = 'INSERT INTO ' . $this->tableName .
            '(name, owner_id)
            VALUES(:name, :owner_id) 
            RETURNING group_id';

        $sqlJoined = 'INSERT INTO ' . $this->joinedTableName .
            '(user_id, group_id)
            VALUES(:user_id, :group_id)';

        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('name', $userGroup->getName());
            $statement->bindValue('owner_id', $userGroup->getOwner()->getId(), ParameterType::INTEGER);
            $result = $statement->executeQuery();

            $groupId = $result->fetchAssociative()["group_id"];
            $userGroup->setGroupId($groupId);

            $statement = $this->connection->prepare($sqlJoined);
            $statement->bindValue('user_id', $userGroup->getOwner()->getId(), ParameterType::INTEGER);
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

    public function delete(UserGroup $userGroup): void
    {
        // TODO: Implement delete() method.
    }
}

