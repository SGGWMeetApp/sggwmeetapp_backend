<?php

namespace App\Repository;

use App\Model\Place;
use App\Model\PublicEvent;
use App\Serializer\PublicEventNormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\DriverException;

class PublicEventRepository extends BaseRepository implements PublicEventRepositoryInterface
{
    private Connection $connection;
    private PublicEventNormalizer $publicEventNormalizer;
    private string $tableName = 'app_owner.events';

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection, PublicEventNormalizer $publicEventNormalizer)
    {
        $this->connection = $connection;
        $this->publicEventNormalizer = $publicEventNormalizer;
    }

    private function getAllEventsQueryString(): string
    {
        return '
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
                b.avatar_path,
                b.description AS userDes

                FROM ' . $this->tableName .' p
                INNER JOIN users b ON p.owner_id = b.user_id
                INNER JOIN locations l ON p.location_id =l.location_id
        ';
    }

    /**
     * @inheritDoc
     * @throws DriverException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     * @throws \Exception
     */
    public function findOrFail(int $eventId): PublicEvent
    {
        $sql = $this->getAllEventsQueryString() . ' WHERE event_id = :eventId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('eventId', $eventId);
            $result = $statement->executeQuery();
            if ($data = $result->fetchAssociative()) {
                return $this->publicEventNormalizer->denormalize($data, 'PublicEvent');
            }
            throw new EntityNotFoundException();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     * @throws \Exception
     */
    public function findAll(): array
    {
        $sql = $this->getAllEventsQueryString();
        try {
            $statement = $this->connection->prepare($sql);
            $result = $statement->executeQuery();
            $publicEvents = [];
            while($data = $result->fetchAssociative()) {
                $publicEvents [] = $this->publicEventNormalizer->denormalize($data, 'PublicEvent');
            }
            return $publicEvents;
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @throws UniqueConstraintViolationException
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws \Exception
     */
    public function findAllForPlace(Place $place): array
    {
        $sql = $this->getAllEventsQueryString() . ' WHERE p.location_id = :locationId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('locationId', $place->getId());
            $result = $statement->executeQuery();
            $placeEvents = [];
            while($data = $result->fetchAssociative()) {
                $placeEvents [] = $this->publicEventNormalizer->denormalize($data, 'PublicEvent');
            }
            return $placeEvents;
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }


    /**
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws UniqueConstraintViolationException
     * @throws DbalException
     * @throws \Exception
     */
    public function findUpcoming(): array
    {
        $test = new \DateTimeImmutable("+7 day");
        $sql = $this->getAllEventsQueryString() .
            sprintf(' WHERE p.start_date < \'%s\'', $test->format(self::DEFAULT_DATETIME_FORMAT));
        try {
            $statement = $this->connection->prepare($sql);
            $result = $statement->executeQuery();
            $publicEvents = [];
            while($data = $result->fetchAssociative()) {
                $publicEvents [] = $this->publicEventNormalizer->denormalize($data, 'PublicEvent');
            }
            return $publicEvents;
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
    public function add(PublicEvent $publicEvent): void
    {
        $sql = 'INSERT INTO ' . $this->tableName .
        ' (location_id, start_date, name, description, owner_id, is_public, can_edit )
        VALUES(:locationID, :startDate, :name, :description, :ownerID, true, :canEdit) RETURNING event_id';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('startDate', $publicEvent->getStartDate()->format(self::DEFAULT_DATETIME_FORMAT));
            $statement->bindValue('name', $publicEvent->getName());
            $statement->bindValue('description', $publicEvent->getDescription());
            $statement->bindValue('locationID', $publicEvent->getLocation()->getId());
            $statement->bindValue('ownerID', $publicEvent->getAuthor()->getId());
            $statement->bindValue('canEdit', $publicEvent->getCanEdit());

            $result = $statement->executeQuery();
            $data = $result->fetchAssociative();
            if ($data) {
                $publicEvent->setId($data['event_id']);
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
    public function update(PublicEvent $publicEvent): void
    {
        $sql = 'UPDATE '. $this->tableName .
            ' SET
                start_date=:startDate,
                name=:name,
                description=:description,
                location_id=:locationId 
            WHERE event_id=:eventId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('startDate', $publicEvent->getStartDate()->format(self::DEFAULT_DATETIME_FORMAT));
            $statement->bindValue('name', $publicEvent->getName());
            $statement->bindValue('description', $publicEvent->getDescription());
            $statement->bindValue('locationId', $publicEvent->getLocation()->getId());
            $statement->bindValue('eventId', $publicEvent->getId());
            $statement->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @throws UniqueConstraintViolationException
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     */
    public function delete(PublicEvent $publicEvent): void
    {
        $sql = 'DELETE FROM '. $this->tableName .
        ' WHERE event_id=:eventId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('eventId', $publicEvent->getId());
            $statement->executeQuery();

        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }
   
}