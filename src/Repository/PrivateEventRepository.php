<?php

namespace App\Repository;

use App\Model\PrivateEvent;
use App\Model\UserGroup;
use App\Serializer\PrivateEventNormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Polyfill\Intl\Icu\Exception\NotImplementedException;
use Doctrine\DBAL\ParameterType;
use Monolog\DateTimeImmutable;

class PrivateEventRepository extends BaseRepository implements PrivateEventRepositoryInterface
{
    private Connection $connection;
    private PrivateEventNormalizer $privateEventNormalizer;
    private string $tableName = 'app_owner.events';

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->privateEventNormalizer = new PrivateEventNormalizer();
    }


    /**
     * @inheritDoc
     * @throws DriverException
     * @throws DbalException
     * @throws SerializerExceptionInterface
     * @throws UniqueConstraintViolationException
     */
    public function findOrFail(int $eventId): PrivateEvent
    {
        $sql = '
            SELECT
                p.event_id,
                p.location_id,
                l.name AS locName,
                l.description AS locDes,
                l.lat,
                l.long,
                l.rating_pct,
                p.name AS eventName,
                p.description AS evntDes,
                p.start_date,
                p.can_edit,
                b.user_id,
                b.first_name,
                b.last_name,
                b.email,
                b.phone_number_prefix,
                b.phone_number,
                b.description AS userDes
                FROM ' . $this->tableName .' p
                INNER JOIN users b ON p.owner_id = b.user_id
                INNER JOIN locations l ON p.location_id = l.location_id
                WHERE p.event_id = :eventId
        ';

        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('eventId', $eventId);
            $result = $statement->executeQuery();
            if ($data = $result->fetchAssociative()) {
                return $this->privateEventNormalizer->denormalize($data, 'PrivateEvent');
            }
            throw new EntityNotFoundException();
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
    public function findAll(int $groupId): array
    {
        $sql = '
            SELECT
                p.event_id,
                p.location_id,
                l.name AS locName,
                l.description AS locDes,
                l.lat,
                l.long,
                l.rating_pct,
                p.name AS eventName,
                p.description AS evntDes,
                p.start_date,
                p.can_edit,
                b.user_id,
                b.first_name,
                b.last_name,
                b.email,
                b.phone_number_prefix,
                b.phone_number,
                b.description AS userDes

            FROM ' . $this->tableName .' p
            INNER JOIN app_owner.users b ON p.owner_id = b.user_id
            INNER JOIN app_owner.locations l ON p.location_id = l.location_id
            WHERE p.group_id = :group_id
            AND p.is_public = false
        ';

        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('group_id', $groupId);
            $result = $statement->executeQuery();
            $privateEvents = [];
            while($data = $result->fetchAssociative()) {
                $privateEvents [] = $this->privateEventNormalizer->denormalize($data, 'PrivateEvent');
            }
            return $privateEvents;
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
    public function add(PrivateEvent $privateEvent): void
    {
        $sql = 'INSERT INTO ' . $this->tableName .
            ' (group_id, location_id, start_date, name, description, owner_id, is_public)
        VALUES(:groupId, :locationID, :startDate, :name, :description, :ownerID, false)
        RETURNING event_id';

        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('startDate', $privateEvent->getStartDate()->format(self::DEFAULT_DATETIME_FORMAT));
            $statement->bindValue('groupId', $privateEvent->getUserGroup()->getGroupId(), ParameterType::INTEGER);
            $statement->bindValue('name', $privateEvent->getName());
            $statement->bindValue('description', $privateEvent->getDescription());
            $statement->bindValue('locationID', $privateEvent->getLocation()->getId(), ParameterType::INTEGER);
            $statement->bindValue('ownerID', $privateEvent->getAuthor()->getId(), ParameterType::INTEGER);

            $result = $statement->executeQuery();

            $eventId = $result->fetchAssociative()["event_id"];
            $privateEvent->setId($eventId);

        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    public function update(PrivateEvent $privateEvent): void
    {
        throw new NotImplementedException('PrivateEvent update() method is not yet implemented.');
//        $sql = 'UPDATE '. $this->tableName .
//            'SET
//                start_date=:startDate,
//                name=:name,
//                description=:description,
//                location_id=:locationId
//            WHERE event_id=:eventId';
//        try {
//            $statement = $this->connection->prepare($sql);
//            $statement->bindValue('startDate', $privateEvent->getStartDate()->format(self::DEFAULT_DATETIME_FORMAT));
//            $statement->bindValue('name', $privateEvent->getName());
//            $statement->bindValue('description', $privateEvent->getDescription());
//            $statement->bindValue('locationId', $privateEvent->getLocationID());
//            $statement->bindValue('eventId', $privateEvent->getId());
//
//            $statement->executeQuery();
//        } catch (DriverException $e) {
//            $this->handleDriverException($e);
//        }
    }

    public function delete(PrivateEvent $privateEvent): void
    {
        throw new NotImplementedException('PrivateEvent delete() method is not yet implemented.');
    }


}