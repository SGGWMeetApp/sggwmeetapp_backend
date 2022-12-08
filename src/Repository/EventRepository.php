<?php

namespace App\Repository;

use App\Model\Event;
use App\Model\Place;
use App\Model\PrivateEvent;
use App\Model\PublicEvent;
use App\Model\UserGroup;
use App\Serializer\EventNormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\ParameterType;

class EventRepository extends BaseRepository implements EventRepositoryInterface
{
    private Connection $connection;
    private EventNormalizer $eventNormalizer;
    private string $tableName = 'app_owner.events';

    /**
     * @param Connection $connection
     * @param EventNormalizer $eventNormalizer
     */
    public function __construct(Connection $connection, EventNormalizer $eventNormalizer)
    {
        $this->connection = $connection;
        $this->eventNormalizer = $eventNormalizer;
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
                p.is_public,
                p.notification_enabled,
                b.user_id,
                b.first_name,
                b.last_name,
                b.email,
                b.phone_number_prefix,
                b.phone_number,
                b.avatar_path,
                b.description AS userDes,
                ARRAY_TO_JSON(ARRAY(SELECT lc.name
                    FROM app_owner.location_categories lc
                    INNER JOIN app_owner.locations_location_categories llc
                    ON llc.category_id = lc.category_id
                    WHERE llc.location_id = p.location_id
                )) AS category_names,
                ARRAY_TO_JSON(ARRAY(SELECT lcp.photo_path
                    FROM app_owner.location_photos lcp
                    WHERE lcp.location_id = p.location_id
                )) AS photo_paths,
                ug.group_id,
                ug.name as group_name,
                ug.owner_id as group_owner_id
                FROM ' . $this->tableName .' p
                INNER JOIN app_owner.users b ON p.owner_id = b.user_id
                INNER JOIN app_owner.locations l ON p.location_id = l.location_id
                LEFT OUTER JOIN app_owner.user_groups ug ON (p.group_id = ug.group_id)
        ';
    }

    /**
     * @inheritDoc
     * @throws DriverException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     * @throws \Exception
     */
    public function findOrFail(int $eventId): Event
    {
        $sql = $this->getAllEventsQueryString() . ' WHERE event_id = :eventId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('eventId', $eventId);
            $result = $statement->executeQuery();

            if ($data = $result->fetchAssociative()) {
                if(!$data["is_public"]) {
                    $data = array_merge($data, $this->findGroupOwner($data['group_id']));
                }
                return $this->eventNormalizer->denormalize($data, 'Event');
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
        $sql = $this->getAllEventsQueryString() . 'WHERE p.is_public = TRUE';
        try {
            $statement = $this->connection->prepare($sql);
            $result = $statement->executeQuery();
            $events = [];
            while($data = $result->fetchAssociative()) {
                $events [] = $this->eventNormalizer->denormalize($data, 'Event');
            }
            return $events;
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
                $placeEvents [] = $this->eventNormalizer->denormalize($data, 'Event');
            }
            return $placeEvents;
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
    public function findAllForGroup(UserGroup $userGroup): array
    {
       $sql = $this->getAllEventsQueryString() . ' WHERE p.group_id = :groupId';

        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('groupId', $userGroup->getGroupId());
            $result = $statement->executeQuery();

            $ownerData = $this->findGroupOwner($userGroup->getGroupId());

            $groupEvents = [];
            while($data = $result->fetchAssociative()) {
                $data = array_merge($data, $ownerData);
                $groupEvents [] = $this->eventNormalizer->denormalize($data, 'Event');
            }
            return $groupEvents;
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
        $sevenDaysFromNow = new \DateTimeImmutable("+7 day");
        $sql = $this->getAllEventsQueryString() .
            sprintf(' WHERE p.is_public = TRUE AND p.start_date < \'%s\'', $sevenDaysFromNow->format(self::DEFAULT_DATETIME_FORMAT));

        try {
            $statement = $this->connection->prepare($sql);
            $result = $statement->executeQuery();
            $events = [];
            while($data = $result->fetchAssociative()) {
                $events [] = $this->eventNormalizer->denormalize($data, 'Event');
            }
            return $events;
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
    private function findGroupOwner(int $userGroupId): array
    {
        $sql =  '
            SELECT
                ug.owner_id as group_owner_id,
               -- u.user_id,
               -- u.username as group_owner_username, 
                u.first_name as group_owner_first_name, 
                u.last_name as group_owner_last_name,
                u.email as group_owner_email,
                u.phone_number_prefix as group_owner_phone_number_prefix,
                u.phone_number as group_owner_phone_number,
                u.description as group_owner_description
            FROM app_owner.user_groups ug
            INNER JOIN users u ON ug.owner_id = u.user_id
            WHERE ug.group_id = :groupId
        ';

        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('groupId', $userGroupId);
            return $statement->executeQuery()->fetchAllAssociative()[0];

        } catch(DriverException $e) {
            $this->handleDriverException($e);
        }
    }


    /**
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws UniqueConstraintViolationException
     * @throws DbalException
     */
    public function add(Event $event): void
    {
        $groupId = $event instanceof PrivateEvent ? $event->getUserGroup()->getGroupId() : null;
        $sql = 'INSERT INTO ' . $this->tableName .
            ' (group_id, location_id, start_date, name, description, owner_id, is_public, can_edit, notification_enabled)
        VALUES(:groupId, :locationID, :startDate, :name, :description, :ownerID, :isPublic, :canEdit, :notificationEnabled) RETURNING event_id';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('groupId', $groupId);
            $statement->bindValue('locationID', $event->getLocation()->getId());
            $statement->bindValue('startDate', $event->getStartDate()->format(self::DEFAULT_DATETIME_FORMAT));
            $statement->bindValue('name', $event->getName());
            $statement->bindValue('description', $event->getDescription());
            $statement->bindValue('ownerID', $event->getAuthor()->getId());
            $statement->bindValue('isPublic', $event instanceof PublicEvent, ParameterType::BOOLEAN);
            $statement->bindValue('canEdit', $event->getCanEdit(), ParameterType::BOOLEAN);
            $statement->bindValue('notificationEnabled', $event->isNotificationsEnabled(), ParameterType::BOOLEAN);
            $result = $statement->executeQuery();
            $data = $result->fetchAssociative();
            if ($data) {
                $event->setId($data['event_id']);
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
    public function update(Event $event): void
    {
        $sql = 'UPDATE '. $this->tableName .
            ' SET
                start_date = :startDate,
                name = :name,
                description = :description,
                location_id = :locationId,
                notification_enabled = :notificationEnabled
            WHERE event_id = :eventId';

        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('startDate', $event->getStartDate()->format(self::DEFAULT_DATETIME_FORMAT));
            $statement->bindValue('name', $event->getName());
            $statement->bindValue('description', $event->getDescription());
            $statement->bindValue('locationId', $event->getLocation()->getId());
            $statement->bindValue('notificationEnabled', $event->isNotificationsEnabled(), ParameterType::BOOLEAN);
            $statement->bindValue('eventId', $event->getId());
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
    public function delete(Event $event): void
    {
        $sql = 'DELETE FROM '. $this->tableName .
            ' WHERE event_id = :eventId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('eventId', $event->getId());
            $statement->executeQuery();

        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

}