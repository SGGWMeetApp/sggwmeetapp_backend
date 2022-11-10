<?php

namespace App\Repository;

use App\Model\PlaceReview;

interface PlaceReviewRepositoryInterface
{
    /**
     * @param int $placeId
     * @param int $authorId
     * @return PlaceReview
     * @throws EntityNotFoundException
     */
    public function findOrFail(int $placeId, int $authorId): PlaceReview;
    public function findAllForPlace(int $placeId): array;
    public function add(PlaceReview $placeReview): void;
    public function update(PlaceReview $placeReview): void;
    public function delete(PlaceReview $placeReview): void;
}