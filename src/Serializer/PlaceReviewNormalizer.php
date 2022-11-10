<?php

namespace App\Serializer;

use App\Model\PlaceReview;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PlaceReviewNormalizer implements NormalizerInterface, DenormalizerInterface
{
    private const SUPPORTED_DENORMALIZER_TYPES = [
        'PlaceReview'
    ];
    /**
     * @inheritDoc
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return [
            'id' => $object->getReviewId(),
            'comment' => $object->getComment(),
            'upvoteCount' => $object->getUpvoteCount(),
            'downvoteCount' => $object->getDownvoteCount(),
            'publicationDate' => $object->getPublicationDate()->format('Y-m-d\TH:i:s\Z'),
            'isPositive' => $object->isPositive()
        ];
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof PlaceReview;
    }

    /**
     * @throws UnsupportedDenormalizerTypeException
     * @throws \Exception
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): PlaceReview
    {
        switch ($type) {
            case 'PlaceReview':
                $placeReview = new PlaceReview(
                    $data['location_id'],
                    $data['user_id'],
                    $data['is_positive'],
                    $data['comment'],
                    new \DateTime($data['publication_date'])
                );
                $placeReview->setReviewId($data['rating_id']);
                $placeReview->setUpvoteCount($data['up_votes']);
                $placeReview->setDownvoteCount($data['down_votes']);
                return $placeReview;
            default:
                throw new UnsupportedDenormalizerTypeException($type, self::SUPPORTED_DENORMALIZER_TYPES);
        }

    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        if(!in_array($type, self::SUPPORTED_DENORMALIZER_TYPES)) {
            return false;
        }
        return true;
    }
}