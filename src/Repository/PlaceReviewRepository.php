<?php

namespace App\Repository;

use App\Model\PlaceReview;
use App\Serializer\PlaceReviewNormalizer;
use App\Serializer\UnsupportedDenormalizerTypeException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\ParameterType;

class PlaceReviewRepository extends BaseRepository implements PlaceReviewRepositoryInterface
{
    private Connection $connection;
    private PlaceReviewNormalizer $objectNormalizer;
    private string $tableName = 'app_owner.location_ratings';

    public function __construct(Connection $connection, PlaceReviewNormalizer $placeReviewNormalizer)
    {
        $this->objectNormalizer = $placeReviewNormalizer;
        $this->connection = $connection;
    }

    /**
     * @throws EntityNotFoundException
     * @throws UnsupportedDenormalizerTypeException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     */
    public function findOrFail(int $placeId, int $reviewId): PlaceReview
    {
        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' pr
            INNER JOIN app_owner.users u
            ON pr.user_id = u.user_id
            WHERE pr.rating_id = :reviewId AND pr.location_id = :placeId';
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('reviewId', $reviewId);
            $statement->bindValue('placeId', $placeId);
            $result = $statement->executeQuery();
            $data = $result->fetchAssociative();
            if(!$data) {
                throw new EntityNotFoundException();
            }
            return $this->objectNormalizer->denormalize($data, 'PlaceReview');
        } catch (DbalException\DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @throws DbalException\DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UnsupportedDenormalizerTypeException
     * @throws UniqueConstraintViolationException
     */
    public function findAllForPlace(int $placeId): array
    {
        $sql = 'SELECT * FROM ' . $this->tableName . ' pr
         INNER JOIN app_owner.users u
         ON pr.user_id = u.user_id
         WHERE location_id = :locationId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('locationId', $placeId);
            $result = $statement->executeQuery();
            $ratings = [];
            while ($data = $result->fetchAssociative()) {
                $ratings [] = $this->objectNormalizer->denormalize($data, 'PlaceReview');
            }
            return $ratings;
        } catch (DbalException\DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @throws DbalException\DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     */
    public function add(PlaceReview $placeReview): void
    {
        $sql = 'INSERT INTO ' . $this->tableName .
            '(location_id, user_id, is_positive, comment, description)
            VALUES(:locationId, :userId, :isPositive, :comment, :description) RETURNING rating_id';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('locationId', $placeReview->getPlaceId());
            $statement->bindValue('userId', $placeReview->getAuthor()->getId());
            $statement->bindValue('isPositive', $placeReview->isPositive(), ParameterType::BOOLEAN);
            $statement->bindValue('comment', $placeReview->getComment());
            $statement->bindValue('description', "-");
            $result = $statement->executeQuery();
            $data = $result->fetchAssociative();
            $placeReview->setReviewId($data['rating_id']);
        } catch (DbalException\DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @throws DbalException\DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     */
    public function update(PlaceReview $placeReview): void
    {
        $sql = 'UPDATE ' . $this->tableName .
            ' SET is_positive = :isPositive, comment = :comment WHERE rating_id = :reviewId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('isPositive', $placeReview->isPositive(), ParameterType::BOOLEAN);
            $statement->bindValue('comment', $placeReview->getComment());
            $statement->bindValue('reviewId', $placeReview->getReviewId());
            $statement->executeQuery();
        } catch (DbalException\DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @throws DbalException\DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UniqueConstraintViolationException
     */
    public function delete(PlaceReview $placeReview): void
    {
        $sql = 'DELETE FROM ' . $this->tableName . ' WHERE rating_id = :reviewId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('reviewId', $placeReview->getReviewId());
            $statement->executeQuery();
        } catch (DbalException\DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @throws DbalException\DriverException
     * @throws UniqueConstraintViolationException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UnsupportedDenormalizerTypeException
     */
    public function findUserReviewForPlace(int $placeId, int $authorId): PlaceReview
    {
        $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE user_id = :authorId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('authorId', $authorId);
            $result = $statement->executeQuery();
            $data = $result->fetchAssociative();
            if(!$data) {
                throw new EntityNotFoundException();
            }
            return $this->objectNormalizer->denormalize($data, 'PlaceReview');
        } catch (DbalException\DriverException $e) {
            $this->handleDriverException($e);
        }
    }


}