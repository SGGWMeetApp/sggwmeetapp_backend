<?php

namespace App\Serializer;

use App\Security\User;
use League\Flysystem\Filesystem;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AuthorUserNormalizer implements NormalizerInterface
{
    private Filesystem $filesystem;

    public function __construct(Filesystem $uploadsFilesystem)
    {
        $this->filesystem = $uploadsFilesystem;
    }

    /**
     * @inheritDoc
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        if (!$object instanceof User) {
            throw new InvalidArgumentException('This normalizer only accepts objects of type App\Security\User');
        }
        return [
            'firstName' => $object->getFirstName(),
            'lastName' => $object->getLastName(),
            'email' => $object->getUserIdentifier(),
            'avatarUrl' => $object->getAvatarUrl() ? $this->filesystem->publicUrl($object->getAvatarUrl()) : null
        ];
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof User;
    }
}