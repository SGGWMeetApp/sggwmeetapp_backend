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

    public function __construct(Connection $connection)
    {
        $this->objectNormalizer = new PlaceReviewNormalizer();
        $this->connection = $connection;
    }

    /**
     * @throws EntityNotFoundException
     * @throws UnsupportedDenormalizerTypeException
     * @throws DbalException
     */
    public function findOrFail(int $placeId, int $authorId): PlaceReview
    {
        try {
            $sql = 'SELECT * FROM ' . $this->tableName . ' pr
            INNER JOIN app_owner.users u
            ON pr.user_id = u.user_id
            WHERE pr.location_id = :locationId AND pr.user_id = :authorId';
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('locationId', $placeId);
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

    /**
     * @throws DbalException\DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     * @throws UnsupportedDenormalizerTypeException
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
     */
    public function add(PlaceReview $placeReview): void
    {
        $sql = 'INSERT INTO ' . $this->tableName .
            '(location_id, user_id, is_positive, comment, up_votes, down_votes, description, publication_date)
            VALUES(:locationId, :userId, :isPositive, :comment, :upVotes, :downVotes, :description, :publicationDate)';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('locationId', $placeReview->getPlaceId());
            $statement->bindValue('userId', $placeReview->getAuthor()->getId());
            $statement->bindValue('isPositive', $placeReview->isPositive(), ParameterType::BOOLEAN);
            $statement->bindValue('comment', $placeReview->getComment());
            $statement->bindValue('upVotes', $placeReview->getUpvoteCount());
            $statement->bindValue('downVotes', $placeReview->getDownvoteCount());
            $statement->bindValue('description', "-");
            $statement->bindValue('publicationDate', $placeReview->getPublicationDate()->format(self::DEFAULT_DATETIME_FORMAT));
            $statement->executeQuery();
        } catch (DbalException\DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @throws DbalException\DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     */
    public function update(PlaceReview $placeReview): void
    {
        $sql = 'UPDATE ' . $this->tableName .
            ' SET is_positive = :isPositive, comment = :comment WHERE location_id = :locationId AND user_id = :authorId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('isPositive', $placeReview->isPositive(), ParameterType::BOOLEAN);
            $statement->bindValue('comment', $placeReview->getComment());
            $statement->bindValue('locationId', $placeReview->getPlaceId());
            $statement->bindValue('authorId', $placeReview->getAuthor()->getId());
            $statement->executeQuery();
        } catch (DbalException\DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @throws DbalException\DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     */
    public function delete(PlaceReview $placeReview): void
    {
        $sql = 'DELETE FROM ' . $this->tableName . ' WHERE user_id = :userId AND location_id = :locationId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('userId', $placeReview->getAuthor()->getId());
            $statement->bindValue('locationId', $placeReview->getPlaceId());
            $statement->executeQuery();
        } catch (DbalException\DriverException $e) {
            $this->handleDriverException($e);
        }
    }
}