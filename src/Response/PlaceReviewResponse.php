<?php

namespace App\Response;

use App\Factory\NormalizerFactory;
use App\Model\PlaceReview;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class PlaceReviewResponse extends JsonResponse
{
    private NormalizerFactory $normalizerFactory;

    /**
     * PlaceReviewResponse constructor
     * @param PlaceReview $placeReview
     * @param NormalizerFactory $normalizerFactory
     * @throws SerializerExceptionInterface
     */
    public function __construct(
        PlaceReview $placeReview,
        NormalizerFactory $normalizerFactory
    )
    {
        $this->normalizerFactory = $normalizerFactory;
        parent::__construct($this->responseData($placeReview));
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function responseData(PlaceReview $placeReview): array
    {
        $placeReviewData = $this->normalizerFactory->getNormalizer($placeReview)->normalize($placeReview);
        $authorData = $this->normalizerFactory->getNormalizer($placeReview->getAuthor())->normalize($placeReview->getAuthor());
        return [
            ...$placeReviewData,
            "author" => $authorData
        ];
    }
}