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

    public function add(PublicEvent $publicEvent): void
    {
        throw new NotImplementedException('PublicEventRepository add() method is not yet implemented.');
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