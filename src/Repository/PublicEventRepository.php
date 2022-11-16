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
        //wyjebalem haslo zeby nie pobieral i w objekcie user ustawiam po prostu ciąg, bo mi tylko do wyswitelania objeckt potrzebny
        //DO MOJEJ ROZKIMNY nie starajcie sie zrozumieć wytłumacze w piątek na zywo XD jak bedize miała sens to wysatczy zapytanie sql bez zabaqy w dane,
        $sql = '
            SELECT
                p.event_id,
                p.location_id,
                p.name,
                p.description,
                p.start_date,
                p.can_edit,
                b.user_id,
                b.first_name,
                b.last_name,
                b.email,
                b.phone_number_prefix,
                b.phone_number,
                b.description

                FROM ' . $this->tableName .' p
                INNER JOIN users b ON p.owner_id = b.user_id
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
        
    }

    public function update(PublicEvent $publicEvent): void //cos mi nie działa na void XD
    {

        //dd($publicEvent);
        $sql = 'UPDATE '. $this->tableName .' SET name=:name, description=:descritpion  WHERE event_id=:eventId';
        
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('name', $publicEvent->getName());
            $statement->bindValue('descritpion', $publicEvent->getDescription());
            $statement->bindValue('eventId', $publicEvent->getId());
           // dd( $statement);
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