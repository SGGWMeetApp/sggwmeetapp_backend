<?php

namespace App\Serializer;

use App\Model\UserGroup;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserGroupNormalizer implements NormalizerInterface, DenormalizerInterface
{

    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        if (!$object instanceof UserGroup) {
            throw new InvalidArgumentException('This normalizer only accepts objects of type App\Model\UserGroup');
        }
        return [
            "id" => $object->getGroupId(),
            "name" => $object->getName(),
        ];
    }

    public function supportsNormalization(mixed $data, string $format = null)
    {
        return $data instanceof UserGroup;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        return new UserGroup(
            $data["group_id"],
            $data['name']
        );
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null)
    {

        // TODO: Implement supportsDenormalization() method.
    }
}

