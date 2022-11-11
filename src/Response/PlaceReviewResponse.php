<?php

namespace App\Response;

use App\Model\PlaceReview;
use App\Serializer\AuthorUserNormalizer;
use App\Serializer\PlaceReviewNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;

class PlaceReviewResponse extends JsonResponse
{

    /**
     * PlaceReviewResponse constructor
     * @param PlaceReview $placeReview
     */
    public function __construct(PlaceReview $placeReview)
    {
        parent::__construct($this->responseData($placeReview));
    }

    public function responseData(PlaceReview $placeReview): array
    {
        $placeReviewNormalizer = new PlaceReviewNormalizer();
        $placeReviewData = $placeReviewNormalizer->normalize($placeReview);
        $authorNormalizer = new AuthorUserNormalizer();
        $authorData = $authorNormalizer->normalize($placeReview->getAuthor());
        return [
            ...$placeReviewData,
            "author" => $authorData
        ];
    }
}