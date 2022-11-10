<?php

namespace App\Model;

class PlaceReview
{
    private ?int $reviewId;
    private int $placeId;
    private bool $isPositive;
    private ?string $comment;
    private int $upvoteCount = 0;
    private int $downvoteCount = 0;
    private \DateTime $publicationDate;
    private int $authorId;

    public function __construct(
        int $placeId,
        int $authorId,
        bool $isPositive,
        ?string $comment = null,
        ?\DateTime $publicationDate = null
    ){
        $this->placeId = $placeId;
        $this->authorId = $authorId;
        $this->isPositive = $isPositive;
        $this->comment = $comment;
        $this->publicationDate = $publicationDate === null ? new \DateTime('now') : $publicationDate;
    }

    public function getReviewId(): ?int
    {
        return $this->reviewId;
    }

    public function setReviewId(?int $reviewId): self
    {
        $this->reviewId = $reviewId;
        return $this;
    }

    public function getPlaceId(): int
    {
        return $this->placeId;
    }

    public function isPositive(): bool
    {
        return $this->isPositive;
    }

    public function setIsPositive(bool $isPositive): self
    {
        $this->isPositive = $isPositive;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getUpvoteCount(): int
    {
        return $this->upvoteCount;
    }

    public function setUpvoteCount(int $upvoteCount): self
    {
        $this->upvoteCount = $upvoteCount;
        return $this;
    }

    public function upvote(): self
    {
        $this->upvoteCount++;
        return $this;
    }

    public function getDownvoteCount(): int
    {
        return $this->downvoteCount;
    }

    public function setDownvoteCount(int $downvoteCount): self
    {
        $this->downvoteCount = $downvoteCount;
        return $this;
    }

    public function downvote(): self
    {
        $this->downvoteCount--;
        return $this;
    }

    public function getPublicationDate(): \DateTime
    {
        return $this->publicationDate;
    }

    public function getAuthorId(): int
    {
        return $this->authorId;
    }

}