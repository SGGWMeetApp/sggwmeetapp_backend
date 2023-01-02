<?php

namespace App\Repository;

use App\Model\PlaceReview;
use App\Security\User;

interface PlaceReviewRepositoryInterface
{
    /**
     * @param int $placeId
     * @param int $reviewId
     * @return PlaceReview
     * @throws EntityNotFoundException
     */
    public function findOrFail(int $placeId, int $reviewId): PlaceReview;

    public function findAllForPlace(int $placeId): array;

    /**
     * @param int $placeId
     * @param int $authorId
     * @return PlaceReview
     * @throws EntityNotFoundException
     */
    public function findUserReviewForPlace(int $placeId, int $authorId): PlaceReview;

    /**
     * @param PlaceReview $placeReview
     * @return void
     * @throws UniqueConstraintViolationException
     */
    public function add(PlaceReview $placeReview): void;

    public function update(PlaceReview $placeReview): void;

    public function delete(PlaceReview $placeReview): void;
}