<?php

namespace App\Repository;

use App\Model\Place;
use App\Serializer\PlaceNormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\DriverException;
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
     */
    public function findOrFail(int $placeId): Place
    {
        $sql = '
            SELECT
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
                    WHERE llc.location_id = :placeId)) AS category_names,
                (SELECT COUNT(lr.location_id) FROM app_owner.location_ratings lr WHERE lr.location_id = p.location_id) AS reviews_count
            FROM ' . $this->tableName .
            ' p WHERE location_id = :placeId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('placeId', $placeId);
            $result = $statement->executeQuery();
            if ($data = $result->fetchAssociative()) {
                return $this->placeNormalizer->denormalize($data, 'Place');
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
     */
    public function findAll(): array
    {
        $sql = '
            SELECT
                p.location_id,
                p.name,
                p.description,
                p.lat,
                p.long,
                p.rating_pct,
                ARRAY_TO_JSON(ARRAY(SELECT lc.name
                    FROM app_owner.location_categories lc
                    INNER JOIN app_owner.locations_location_categories llc
                    ON llc.category_id = lc.category_id)) AS category_names,
                (SELECT COUNT(lr.location_id) FROM app_owner.location_ratings lr WHERE lr.location_id = p.location_id) AS reviews_count
            FROM ' . $this->tableName . ' p';
        try {
            $statement = $this->connection->prepare($sql);
            $result = $statement->executeQuery();
            $places = [];
            while($data = $result->fetchAssociative()) {
                $places [] = $this->placeNormalizer->denormalize($data, 'Place');
            }
            return $places;
        } catch (DriverException $e) {
            $this->handleDriverException($e);
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