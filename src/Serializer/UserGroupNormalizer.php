<?php

namespace App\Serializer;

use App\Model\UserGroup;
use App\Security\User;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserGroupNormalizer implements NormalizerInterface, DenormalizerInterface
{

    /**
     * @inheritDoc
     */
    public function normalize(mixed $object, string $format = null, array $context = []): float|array|bool|\ArrayObject|int|string|null
    {
        if (!$object instanceof UserGroup) {
            throw new InvalidArgumentException('This normalizer only accepts objects of type App\Model\UserGroup');
        }

        $owner = $object->getOwner();
        $adminData = [
            "firstName" => $owner->getFirstName(),
            "lastName" => $owner->getLastName(),
        ];

        $userGroupData = [
            "id" => $object->getGroupId(),
            "name" => $object->getName(),
            "memberCount" => $object->getMemberCount(),
            "adminData" => $adminData,
            "incomingEventsCount" => $object->getIncomingEventsCount()
        ];

        $normalizedUsers = [];
        /** @var User $user */
        foreach($object->getUsers() as $user) {
            $isAdmin = $user->isEqualTo($owner);
            $normalizedUsers [] = [
                "id" => $user->getId(),
                "firstName" => $user->getFirstName(),
                "lastName" => $user->getLastName(),
                "email" => $user->getEmail(),
                "registrationDate" => $user->getRegistrationDate()->format('Y-m-d\TH:i:s.v\Z'),
                "isAdmin" => $isAdmin
            ];
        }

        return [
            ...$userGroupData,
            "users" => $normalizedUsers
        ];

    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof UserGroup;
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): UserGroup
    {
        $users = json_decode($data["users"]);

        $userGroup = new UserGroup(
            $data["group_id"],
            $data['name'],
            null,
            count($users)
        );

        foreach($users as $userData) {
            $user = new User(
                $userData->user_id,
                $userData->first_name,
                $userData->last_name,
                $userData->email,
                '',
                $userData->phone_number_prefix,
                $userData->phone_number,
                $userData->description,
                new \DateTime($userData->creation_date),
                ['ROLE_USER']);

            $userGroup->addUser($user);

            if($user->getId() == $data["owner_id"]) {
                $userGroup->setOwner($user);
            }
        }
        $userGroup->setIncomingEventsCount($data['incoming_events_count']);
        return $userGroup;
    }

    /**
     * @inheritDoc
     */
    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return is_array($data) && $type == 'UserGroup';
    }
}

