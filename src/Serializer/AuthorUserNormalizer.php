<?php

namespace App\Serializer;

use App\Security\User;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AuthorUserNormalizer implements NormalizerInterface
{

    /**
     * @inheritDoc
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return [
            'firstName' => $object->getFirstName(),
            'lastName' => $object->getLastName(),
            'email' => $object->getUserIdentifier(),
            'avatarUrl' => ''
        ];
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof User;
    }
}