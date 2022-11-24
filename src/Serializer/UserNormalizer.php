<?php

namespace App\Serializer;

use App\Security\User;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserNormalizer implements NormalizerInterface, DenormalizerInterface
{

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function normalize(mixed $object, string $format = null, array $context = []): float|array|bool|\ArrayObject|int|string|null
    {
        if (!$object instanceof User) {
            throw new InvalidArgumentException('This normalizer only accepts objects of type App\Security\User');
        }
        //TODO: Add proper avatar url when it gets implemented
        $normalizedUser = [
            'id' => $object->getId(),
            'email' => $object->getEmail(),
            'firstName' => $object->getFirstName(),
            'lastName' => $object->getLastName(),
            'phoneNumberPrefix' => $object->getPhonePrefix(),
            'phoneNumber' => $object->getPhone(),
            'description' => $object->getDescription(),
            'avatarUrl' => ''
        ];
        if (array_key_exists('modelProperties', $context) && is_array($context['modelProperties'])) {
            $userProperties = [];
            try {
                foreach ($context['modelProperties'] as $modelProperty) {
                    $userProperties [$modelProperty] = $normalizedUser[$modelProperty];
                }
                return $userProperties;
            } catch (\OutOfBoundsException) {
                throw new \Exception("One or more properties do not exist for user model in UserNormalizer normalize() method.");
            }
        }
        return $normalizedUser;
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof User;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): User
    {
        return new User(
            $data['user_id'],
            trim($data['first_name']),
            trim($data['last_name']),
            $data['email'],
            $data['password'],
            trim($data['phone_number_prefix']),
            trim($data['phone_number']),
            trim($data['description']),
            ['ROLE_USER']
        );
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type == User::class;
    }
}