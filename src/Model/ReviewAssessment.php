<?php

namespace App\Model;

class ReviewAssessment
{
    private int $placeId;
    private int $authorId;
    private int $reviewerId;
    private bool $isPositive;

    /**
     * @param int $placeId
     * @param int $authorId
     * @param int $reviewerId
     * @param bool $isPositive
     */
    public function __construct(int $placeId, int $authorId, int $reviewerId, bool $isPositive)
    {
        $this->placeId = $placeId;
        $this->authorId = $authorId;
        $this->reviewerId = $reviewerId;
        $this->isPositive = $isPositive;
    }

    /**
     * @return int
     */
    public function getPlaceId(): int
    {
        return $this->placeId;
    }

    /**
     * @return int
     */
    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    /**
     * @return int
     */
    public function getReviewerId(): int
    {
        return $this->reviewerId;
    }

    /**
     * @return bool
     */
    public function isPositive(): bool
    {
        return $this->isPositive;
    }

    /**
     * @param bool $isPositive
     */
    public function setIsPositive(bool $isPositive): void
    {
        $this->isPositive = $isPositive;
    }

}