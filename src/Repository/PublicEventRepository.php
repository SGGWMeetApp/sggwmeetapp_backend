<?php

namespace App\Repository;

use App\Model\PublicEvent;
use App\Serializer\PublicEventNormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Polyfill\Intl\Icu\Exception\NotImplementedException;

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
        $sql = '
            SELECT
                p.event_id,
                p.location_id,
                p.name,
                p.description,
                p.start_date,
                p.can_edit,
                ARRAY_TO_JSON(ARRAY(SELECT lc.first_name
                FROM app_owner.users lc
                WHERE lc.user_id = p.owner_id) ) AS author
            FROM ' . $this->tableName .
            ' p WHERE event_id = :eventId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('eventId', $eventId);
            $result = $statement->executeQuery();
            if ($data = $result->fetchAssociative()) {
               // dd($data);
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
        $sql = '';
        try {
            $statement = $this->connection->prepare($sql);
            $result = $statement->executeQuery();
            $events = [];
            while($data = $result->fetchAssociative()) {
                $events [] = $this->publicEventNormalizer->denormalize($data, 'PublicEvent');
            }
            return $events;
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }


    public function add(PublicEvent $publicEvent): void
    {
        //Dodanie do bazy
    

        //throw new NotImplementedException('PublicEventRepository add() method is not yet implemented.');
    }

    public function update(PublicEvent $publicEvent): void
    {
        throw new NotImplementedException('PublicEven update() method is not yet implemented.');
    }

    public function delete(PublicEvent $publicEvent): void
    {
        throw new NotImplementedException('PublicEven update() method is not yet implemented.');
    }
      
   
}