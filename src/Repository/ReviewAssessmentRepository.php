<?php

namespace App\Repository;

use App\Model\ReviewAssessment;
use App\Serializer\ReviewAssessmentNormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterfaceAlias;

class ReviewAssessmentRepository extends BaseRepository implements ReviewAssessmentRepositoryInterface
{
    private Connection $connection;
    private string $tableName = 'app_owner.rating_reviews';
    private ReviewAssessmentNormalizer $reviewAssessmentNormalizer;

    /**
     * @param Connection $connection
     * @param ReviewAssessmentNormalizer $reviewAssessmentNormalizer
     */
    public function __construct(Connection $connection, ReviewAssessmentNormalizer $reviewAssessmentNormalizer)
    {
        $this->connection = $connection;
        $this->reviewAssessmentNormalizer = $reviewAssessmentNormalizer;
    }


    /**
     * @inheritDoc
     * @param int $placeId
     * @param int $reviewId
     * @param int $reviewerId
     * @return ReviewAssessment
     * @throws DbalException
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws UniqueConstraintViolationException
     * @throws SerializerExceptionInterfaceAlias
     */
    public function findOrFail(int $placeId, int $reviewId, int $reviewerId): ReviewAssessment
    {
        $sql = 'SELECT
                rr.rating_id,
                rr.user_id AS reviewer_id,
                rr.is_up_vote,
                rr.creation_date,
                lr.user_id AS author_id
                FROM ' . $this->tableName . ' rr 
                INNER JOIN app_owner.location_ratings lr ON lr.rating_id = rr.rating_id 
                WHERE rr.rating_id = :reviewId AND rr.user_id = :reviewerId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('reviewId', $reviewId);
            $statement->bindValue('reviewerId', $reviewerId);
            $result = $statement->executeQuery();
            $data = $result->fetchAssociative();
            if(!$data) {
                throw new EntityNotFoundException();
            }
            return $this->reviewAssessmentNormalizer->denormalize($data, 'ReviewAssessment');
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
    public function findUserAssessmentsForReviews(int $user_id, array $reviewIds): array
    {
        if (count($reviewIds) < 1) return [];
        $qMarks = str_repeat('?,', count($reviewIds) - 1) . '?';
        $assessments = [];
        foreach ($reviewIds as $id) {
            $assessments[$id] = ['review_id' => $id, 'isPositive' => null];
        }
        $sql = "
            SELECT DISTINCT
                rr.rating_id as rating_id,
                rr.is_up_vote as is_up_vote
            FROM ".$this->tableName." rr
            WHERE rr.rating_id IN ($qMarks)";
        try {
            $statement = $this->connection->prepare($sql);
            $result = $statement->executeQuery($reviewIds);
            $rawAssessments = $result->fetchAllAssociative();
            foreach ($rawAssessments as $assessment) {
                $assessments[$assessment['rating_id']]['isPositive'] = $assessment['is_up_vote'];
            }
            return $assessments;
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
    public function add(ReviewAssessment $reviewAssessment): void
    {
        $sql = 'INSERT INTO ' . $this->tableName .
            ' (rating_id, user_id, is_up_vote)
            VALUES (:ratingId, :userId, :isUpVote)';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('ratingId', $reviewAssessment->getReviewId());
            $statement->bindValue('userId', $reviewAssessment->getReviewerId());
            $statement->bindValue('isUpVote', $reviewAssessment->isPositive(), ParameterType::BOOLEAN);
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
    public function update(ReviewAssessment $reviewAssessment): void
    {
        $sql = 'UPDATE ' . $this->tableName .
            ' SET is_up_vote = :isUpVote WHERE rating_id = :ratingId AND user_id = :userId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('isUpVote', $reviewAssessment->isPositive(), ParameterType::BOOLEAN);
            $statement->bindValue('ratingId', $reviewAssessment->getReviewId());
            $statement->bindValue('userId', $reviewAssessment->getReviewerId());
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
    public function delete(ReviewAssessment $reviewAssessment): void
    {
        $sql = 'DELETE FROM ' . $this->tableName . ' WHERE rating_id = :ratingId AND user_id = :userId';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('ratingId', $reviewAssessment->getReviewId());
            $statement->bindValue('userId', $reviewAssessment->getReviewerId());
            $statement->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }


}