<?php

namespace App\Repository;

use App\Model\ReviewAssessment;
use Symfony\Polyfill\Intl\Icu\Exception\NotImplementedException;

class ReviewAssessmentRepository extends BaseRepository implements ReviewAssessmentRepositoryInterface
{

    /**
     * @inheritDoc
     */
    public function findOrFail(int $placeId, int $authorId, int $reviewerId): ReviewAssessment
    {
        // TODO: Implement findOrFail() method
        throw new NotImplementedException('Find or fail is not yet implemented');
    }

    public function addReviewAssessment(ReviewAssessment $reviewAssessment): void
    {
        // TODO: Implement addReviewAssessment() method.
    }

    public function updateReviewAssessment(ReviewAssessment $reviewAssessment): void
    {
        // TODO: Implement updateReviewAssessment() method.
    }
}