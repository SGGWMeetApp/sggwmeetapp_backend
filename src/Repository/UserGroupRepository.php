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

    public function findAll(): array
    {
        // TODO: Implement findAll() method.
    }

    /**
     * @throws DbalException\DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     */
    public function add(UserGroup $userGroup): int
    {
        $sql = 'INSERT INTO ' . $this->tableName .
            '(name, owner_id)
            VALUES(:name, :owner_id)';

        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('name', $userGroup->getName());
            $statement->bindValue('owner_id', $userGroup->getOwner()->getId(), ParameterType::INTEGER);
            $statement->executeQuery();

            $groupId = $this->connection->lastInsertId();
            // TODO: add data to join table

        } catch (DbalException\DriverException $e) {
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

