<?php

namespace App\Serializer;

use App\Model\ReviewAssessment;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ReviewAssessmentNormalizer implements NormalizerInterface, DenormalizerInterface
{

    /**
     * @inheritDoc
     */
    public function normalize(mixed $object, string $format = null, array $context = []): float|array|bool|\ArrayObject|int|string|null
    {
        if(!$object instanceof ReviewAssessment) {
            throw new InvalidArgumentException("This normalizer only supports instances of ReviewAssessment::class");
        }
        return [
            'review_id' => $object->getReviewId(),
            'author_id' => $object->getAuthorId(),
            'reviewer_id' => $object->getAuthorId(),
            'is_positive' => $object->isPositive()
        ];
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof ReviewAssessment;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): ReviewAssessment
    {
        if(!is_array($data)) {
            throw new InvalidArgumentException("This normalizer only supports denormalization of arrays");
        }
        return new ReviewAssessment(
            $data['rating_id'],
            $data['author_id'],
            $data['reviewer_id'],
            $data['is_up_vote']
        );
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return is_array($data) && $type == 'ReviewAssessment';
    }
}