<?php

namespace App\Serializer;

use App\Model\PlaceReview;
use App\Security\User;
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
            'place_id' => $object->getPlaceId(),
            'author_id' => $object->getAuthor()->getId(),
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
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
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
                // TODO: Update this user initialization when db gets updated (password + roles)
                $user = new User(
                    $data['user_id'],
                    $data['first_name'],
                    $data['last_name'],
                    $data['email'],
                    $data['password'],
                    $data['phone_number_prefix'],
                    $data['phone_number'],
                    $data['description'],
                    ['ROLE_USER']);
                $placeReview = new PlaceReview(
                    $data['location_id'],
                    $user,
                    $data['is_positive'],
                    $data['comment'],
                    new \DateTime($data['publication_date'])
                );
                $placeReview->setUpvoteCount($data['up_votes']);
                $placeReview->setDownvoteCount($data['down_votes']);
                return $placeReview;
            default:
                throw new UnsupportedDenormalizerTypeException($type, self::SUPPORTED_DENORMALIZER_TYPES);
        }

    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        if(!in_array($type, self::SUPPORTED_DENORMALIZER_TYPES)) {
            return false;
        }
        return true;
    }
}