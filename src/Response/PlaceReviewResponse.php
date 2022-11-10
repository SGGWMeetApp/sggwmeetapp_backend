<?php

namespace App\Response;

use App\Model\PlaceReview;
use App\Security\User;
use App\Serializer\AuthorUserNormalizer;
use App\Serializer\PlaceReviewNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;

class PlaceReviewResponse extends JsonResponse
{

    /**
     * PlaceReviewResponse constructor
     * @param PlaceReview $placeReview
     * @param User $author
     */
    public function __construct(PlaceReview $placeReview, User $author)
    {
        parent::__construct($this->responseData($placeReview, $author));
    }

    public function responseData(PlaceReview $placeReview, User $author): array
    {
        $placeReviewNormalizer = new PlaceReviewNormalizer();
        $placeReviewData = $placeReviewNormalizer->normalize($placeReview);
        $authorNormalizer = new AuthorUserNormalizer();
        $authorData = $authorNormalizer->normalize($author);
        return [
            ...$placeReviewData,
            "author" => $authorData
        ];
    }
}