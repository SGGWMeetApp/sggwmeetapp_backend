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

class PlaceRepository extends BaseRepository implements PlaceRepositoryInterface
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

    private function createFindAllQueryBuilder(Connection $connection): QueryBuilder
    {
        $queryBuilder = new QueryBuilder($connection);
        $queryBuilder
            ->select('
                p.location_id,
                p.name,
                p.description,
                p.lat,
                p.long,
                p.rating_pct,
                ARRAY_TO_JSON(ARRAY(SELECT lc.name
                    FROM app_owner.location_categories lc
                    INNER JOIN app_owner.locations_location_categories llc
                    ON llc.category_id = lc.category_id
                    WHERE llc.location_id = p.location_id
                )) AS category_names,
                p.ratings_number AS reviews_count'
            )
            ->from($this->tableName, 'p');
        return $queryBuilder;
    }

    /**
     * @throws SerializerExceptionInterface
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     */
    public function findAll(PlaceFilters $filters): array
    {
        $queryBuilder = $this->createFindAllQueryBuilder($this->connection);
        $this->addPlaceFilters($queryBuilder, $filters);
        try {
            $result = $queryBuilder->executeQuery();
            $places = [];
            while($data = $result->fetchAssociative()) {
                $places [] = $this->placeNormalizer->denormalize($data, 'Place');
            }
            return $places;
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    private function addPlaceFilters(QueryBuilder $queryBuilder, PlaceFilters $filters)
    {
        if($filters->getName() !== null) {
            $queryBuilder->andWhere($queryBuilder->expr()->like('LOWER(p.name)', ':name'));
            $queryBuilder->setParameter('name', '%' . strtolower($filters->getName()) . '%');
        }
        if($filters->getCategoryCodes() !== null) {
            $queryBuilder->andWhere('
            ARRAY(SELECT lc.name::text
            FROM app_owner.location_categories lc
            INNER JOIN app_owner.locations_location_categories llc
            ON llc.category_id = lc.category_id
            WHERE llc.location_id = p.location_id) && ARRAY[:categoryCodes]'
            );
            $queryBuilder->setParameter(
                'categoryCodes',
                $filters->getCategoryCodes(),
                Connection::PARAM_STR_ARRAY
            );
        }
    }

    public function add(Place $place): void
    {
        throw new NotImplementedException('PlaceRepository add() method is not yer implemented.');
    }

    public function update(Place $place): void
    {
        throw new NotImplementedException('PlaceRepository update() method is not yet implemented.');
    }

    /**
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     */
    public function delete(Place $place): void
    {
        $sql = 'DELETE FROM ' . $this->tableName . ' WHERE location_id = :placeId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('placeId', $place->getId());
            $statement->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }
}