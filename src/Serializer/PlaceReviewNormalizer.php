<?php

namespace App\Serializer;

use App\Model\PlaceReview;
use App\Security\User;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PlaceReviewNormalizer implements NormalizerInterface, DenormalizerInterface
{
    private UserNormalizer $userNormalizer;

    private const SUPPORTED_DENORMALIZER_TYPES = [
        'PlaceReview'
    ];

    public function __construct(UserNormalizer $userNormalizer)
    {
        $this->userNormalizer = $userNormalizer;
    }


    /**
     * @inheritDoc
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        if (!$object instanceof PlaceReview) {
            throw new InvalidArgumentException('This normalizer only accepts objects of type App\Model\PlaceReview');
        }
        $normalizedAuthor = $this->userNormalizer->normalize($object->getAuthor(), 'json', [
            'modelProperties' => UserNormalizer::AUTHOR_PROPERTIES
        ]);
        return [
            'id' => $object->getReviewId(),
            'comment' => $object->getComment(),
            'upvoteCount' => $object->getUpvoteCount(),
            'downvoteCount' => $object->getDownvoteCount(),
            'publicationDate' => $object->getPublicationDate()->format('Y-m-d\TH:i:s\Z'),
            'isPositive' => $object->isPositive(),
            'author' => $normalizedAuthor
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
                $user = new User(
                    $data['user_id'],
                    $data['first_name'],
                    $data['last_name'],
                    $data['email'],
                    $data['password'],
                    $data['phone_number_prefix'],
                    $data['phone_number'],
                    $data['description'],
                    new \DateTime($data['creation_date']),
                    ['ROLE_USER']);
                $user->setAvatarUrl($data['avatar_path']);
                $placeReview = new PlaceReview(
                    $data['rating_id'],
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