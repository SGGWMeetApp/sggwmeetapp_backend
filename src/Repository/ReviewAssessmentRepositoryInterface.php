<?php

namespace App\Repository;

use App\Model\ReviewAssessment;

interface ReviewAssessmentRepositoryInterface
{
    /**
     * @throws EntityNotFoundException
     */
    public function findOrFail(int $placeId, int $reviewId, int $reviewerId): ReviewAssessment;

    public function add(ReviewAssessment $reviewAssessment): void;

    public function update(ReviewAssessment $reviewAssessment): void;

    public function delete(ReviewAssessment $reviewAssessment): void;
}