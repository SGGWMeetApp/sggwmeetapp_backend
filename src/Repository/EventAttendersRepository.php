<?php

namespace App\Repository;

use App\Filter\PlaceFilters;
use App\Model\Place;
use App\Serializer\PlaceNormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Polyfill\Intl\Icu\Exception\NotImplementedException;

class EventAttendersRepository extends BaseRepository implements EventAttendersRepositoryInterface
{
    private Connection $connection;
    private PlaceNormalizer $placeNormalizer;
    private string $tableName = 'app_owner.locations';

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->placeNormalizer = new PlaceNormalizer();
    }

    /**
     * @inheritDoc
     * @throws DriverException
     * @throws DbalException
     * @throws SerializerExceptionInterface
     * @throws UniqueConstraintViolationException
     */
    public function findOrFail(int $placeId): Place
    {
        $queryBuilder = $this->createFindAllQueryBuilder($this->connection);
        $queryBuilder->andWhere('location_id = :placeId');
        $queryBuilder->setParameter('placeId', $placeId);
        try {
            $result = $queryBuilder->executeQuery();
            if ($data = $result->fetchAssociative()) {
                return $this->placeNormalizer->denormalize($data, 'Place');
            }
            throw new EntityNotFoundException();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }