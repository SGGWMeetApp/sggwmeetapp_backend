<?php

namespace App\Repository;

use App\Model\Event;
use App\Model\Place;
use App\Model\PrivateEvent;
use App\Model\PublicEvent;
use App\Model\UserGroup;
use App\Security\User;
use App\Serializer\EventNormalizer;
use App\Serializer\UserNormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;

class EventRepository extends BaseRepository implements EventRepositoryInterface
{
    private Connection $connection;
    private EventNormalizer $eventNormalizer;
    private UserNormalizer $userNormalizer;

    private string $tableName = 'app_owner.events';
    private string $attendersTableName = 'app_owner.event_attenders';

    /**
     * @param Connection $connection
     * @param EventNormalizer $eventNormalizer
     * @param UserNormalizer $userNormalizer
     */
    public function __construct(Connection $connection, EventNormalizer $eventNormalizer, UserNormalizer $userNormalizer)
    {
        $this->connection = $connection;
        $this->eventNormalizer = $eventNormalizer;
        $this->userNormalizer = $userNormalizer;
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
                l.text_location AS text_location,
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
                b.creation_date AS "userRegistrationDate",
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
                ug.owner_id as group_owner_id,
                (SELECT COUNT(ea.user_id) FROM app_owner.event_attenders ea WHERE ea.event_id = p.event_id) as "attendersCount"
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
    public function findAllPublicEvents(): array
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
    public function findAllPublicEventsForPlace(Place $place): array
    {
        $sql = $this->getAllEventsQueryString() . ' WHERE p.location_id = :locationId AND p.is_public = TRUE';
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
    public function findUpcomingPublicEvents(): array
    {
        $sevenDaysFromNow = new \DateTimeImmutable('+7 day');
        $now = new \DateTimeImmutable('now');
        $sql = $this->getAllEventsQueryString() .
            ' WHERE p.is_public = TRUE AND p.start_date > :date_low AND p.start_date < :date_high';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('date_low', $now->format(self::DEFAULT_DATETIME_FORMAT));
            $statement->bindValue('date_high', $sevenDaysFromNow->format(self::DEFAULT_DATETIME_FORMAT));
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

    /**
     * @throws UniqueConstraintViolationException
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     */
    public function addUserToEventAttenders(User $user, Event $event): void
    {
        $sql = 'INSERT INTO '.$this->attendersTableName.' (event_id, user_id) VALUES (:event_id, :user_id)';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('event_id', $event->getId());
            $statement->bindValue('user_id', $user->getId());
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
    public function removeUserFromEventAttenders(User $user, Event $event): void
    {
        $sql = 'DELETE FROM '.$this->attendersTableName.' WHERE event_id=:event_id AND user_id=:user_id';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('event_id', $event->getId());
            $statement->bindValue('user_id', $user->getId());
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
     * @throws \Exception
     */
    public function getAttenders(Event $event): array
    {
        $sql = 'SELECT * FROM app_owner.users WHERE user_id IN (SELECT user_id FROM '.$this->attendersTableName.' WHERE event_id=:event_id)';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('event_id', $event->getId());
            $result = $statement->executeQuery();
            $users = [];
            while ($data = $result->fetchAssociative()) {
                $users [] = $this->userNormalizer->denormalize($data, User::class);
            }
            return $users;
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
    public function findAllForUser(User $user): array
    {
        $sql = $this->getAllEventsQueryString() . ' WHERE event_id IN 
        (SELECT event_id FROM '.$this->attendersTableName.' WHERE user_id=:user_id AND is_going=TRUE)';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('user_id', $user->getId());
            $result = $statement->executeQuery();
            $events = [];
            while($data = $result->fetchAssociative()) {
                if($data['group_id'] !== null) {
                    $data = array_merge($data, $this->findGroupOwner($data['group_id']));
                }
                $events [] = $this->eventNormalizer->denormalize($data, 'Event');
            }
            return $events;
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
    public function checkUserAttendance(User $user, Event ...$events): array
    {
        $attendance = [];
        foreach ($events as $event) {
            $attendance[$event->getId()] = [
                'event_id' => $event->getId(),
                'attends' => false
            ];
        }
        $queryBuilder = new QueryBuilder($this->connection);
        $queryBuilder
            ->select('user_id, event_id')
            ->from($this->attendersTableName)
            ->where('user_id=:user_id')
            ->setParameter('user_id', $user->getId(), ParameterType::INTEGER);
        try {
            $result = $queryBuilder->executeQuery();
            while($data = $result->fetchAssociative()) {
                $attendance[$data['event_id']]['attends'] = true;
            }
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
        return $attendance;
    }

    /**
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     */
    public function findUpcommingEventAttenders(int $upcommingTimeInMinutes, int $notificationIntervalInMinutes): array
    {
        $events = $this->getUpcommingEvents($upcommingTimeInMinutes, $notificationIntervalInMinutes);
        $eventAttenders = [];
        foreach ($events as $event) {
            $eventAttenders[] = [
                'event' => $event, 
                'attenders' => $this->getAttenders(Event $event)
            ];
        }
        return $eventAttenders;
    }

    /**
     * @throws DriverException
     * @throws DbalException
     */
    private function getUpcommingEvents(int $upcommingTimeInMinutes, int $notificationIntervalInMinutes): array
    {
        $upcommingLowTime = new \DateTimeImmutable('+'. $upcommingTimeInMinutes - $notificationIntervalInMinutes .' minute');
        $upcomminghighTime = new \DateTimeImmutable('+' . $upcommingTimeInMinutes + $notificationIntervalInMinutes .' minute');
        $sql = $this->getAllEventsQueryString() .
            ' WHERE p.start_date < :date_low AND p.start_date > :date_high';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('date_low', $upcommingLowTime->format(self::DEFAULT_DATETIME_FORMAT));
            $statement->bindValue('date_high', $upcomminghighTime->format(self::DEFAULT_DATETIME_FORMAT));
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

}