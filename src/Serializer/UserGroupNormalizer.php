<?php

namespace App\Serializer;

use App\Model\UserGroup;
use App\Security\User;

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
            "memberCount" => $object->getMemberCount(),
            "adminData" => [
                "firstname" => $object->getOwner()->getFirstName(),
                "lastname" => $object->getOwner()->getLastName(),
                "isUserAdmin" => true
            ],
            "incomingEventsCount" => 1
        ];

    }

    public function supportsNormalization(mixed $data, string $format = null)
    {
        return $data instanceof UserGroup;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        $owner = json_decode($data["admin_data"], true);

        return new UserGroup(
            $data["group_id"],
            $data['name'],
            new User(1, $owner[0], 'lastName', 'email', '', 'phonePrefix', 111111111, 'description', ['ROLE_USER']),
            $data['member_count']
            //incoming events count
        );
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type == 'UserGroup';
    }
}

