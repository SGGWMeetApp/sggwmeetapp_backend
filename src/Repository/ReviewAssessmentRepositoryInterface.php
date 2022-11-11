<?php

namespace App\Repository;

use App\Model\ReviewAssessment;

interface ReviewAssessmentRepositoryInterface
{
    /**
     * @throws EntityNotFoundException
     */
    public function findOrFail(int $placeId, int $authorId, int $reviewerId): ReviewAssessment;
    public function addReviewAssessment(ReviewAssessment $reviewAssessment): void;
    public function updateReviewAssessment(ReviewAssessment $reviewAssessment): void;
}