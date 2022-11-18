<?php

namespace App\Repository;

use App\Model\PublicEvent;
use App\Serializer\PublicEventNormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Polyfill\Intl\Icu\Exception\NotImplementedException;
use Doctrine\DBAL\ParameterType;
use Monolog\DateTimeImmutable;

class PublicEventRepository extends BaseRepository implements PublicEventRepositoryInterface
{
    private Connection $connection;
    private PublicEventNormalizer $publicEventNormalizer;
    private string $tableName = 'app_owner.events';

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->publicEventNormalizer = new PublicEventNormalizer();
    }


    /**
     * @inheritDoc
     * @throws DriverException
     * @throws DbalException
     * @throws SerializerExceptionInterface
     * @throws UniqueConstraintViolationException
     */
    public function findOrFail(int $eventId): PublicEvent
    {
        //wyjebalem haslo zeby nie pobieral i w objekcie user ustawiam po prostu ciąg, bo mi tylko do wyswitelania objeckt potrzebny
        //DO MOJEJ ROZKIMNY nie starajcie sie zrozumieć wytłumacze w piątek na zywo XD jak bedize miała sens to wysatczy zapytanie sql bez zabaqy w dane,
        $sql = '
            SELECT
                p.event_id,
                p.location_id,
                l.name AS locName,
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
                INNER JOIN locations l ON p.location_id =l.location_id
                WHERE event_id = :eventId
                ';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('eventId', $eventId);
            $result = $statement->executeQuery();
            if ($data = $result->fetchAssociative()) {
                //dd($data);
                return $this->publicEventNormalizer->denormalize($data, 'PublicEvent');
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
    public function findAll(): array
    {
        $sql = '
            SELECT
                p.event_id,
                p.location_id,
                l.name AS locName,
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
            INNER JOIN locations l ON p.location_id =l.location_id
            
            ';
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
        ' (location_id, start_date, name, description, owner_id, is_public)
        VALUES(:locationID, :startDate, :name, :description, :ownerID, true)';
        
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('startDate', $publicEvent->getStartDate()->format(self::DEFAULT_DATETIME_FORMAT));
            $statement->bindValue('name', $publicEvent->getName());
            $statement->bindValue('description', $publicEvent->getDescription());
            $statement->bindValue('locationID', $publicEvent->getLocationID());
            $statement->bindValue('ownerID', $publicEvent->getAuthor()->getId());

            $statement->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    public function update(PublicEvent $publicEvent): void 
    {
        $sql = 'UPDATE '. $this->tableName .
            'SET 
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
            $statement->bindValue('locationId', $publicEvent->getLocationID());
            $statement->bindValue('eventId', $publicEvent->getId());
            
            $statement->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    public function delete(PublicEvent $publicEvent): void
    {
        throw new NotImplementedException('PublicEven update() method is not yet implemented.');
    }
      
   
}