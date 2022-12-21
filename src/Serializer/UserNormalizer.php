<?php

namespace App\Serializer;

use App\Model\NotificationSetting;
use App\Security\User;
use League\Flysystem\Filesystem;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserNormalizer implements NormalizerInterface, DenormalizerInterface
{
    private Filesystem $filesystem;

    public const AUTHOR_PROPERTIES = ['firstName', 'lastName', 'email', 'avatarUrl'];

    public function __construct(Filesystem $uploadsFilesystem)
    {
        $this->filesystem = $uploadsFilesystem;
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function normalize(mixed $object, string $format = null, array $context = []): float|array|bool|\ArrayObject|int|string|null
    {
        if (!$object instanceof User) {
            throw new InvalidArgumentException('This normalizer only accepts objects of type App\Security\User');
        }
        $normalizedUser = [
            'id' => $object->getId(),
            'email' => $object->getEmail(),
            'firstName' => $object->getFirstName(),
            'lastName' => $object->getLastName(),
            'phoneNumberPrefix' => $object->getPhonePrefix(),
            'phoneNumber' => $object->getPhone(),
            'description' => $object->getDescription(),
            'registrationDate' => $object->getRegistrationDate()->format('Y-m-d\TH:i:s.v\Z'),
            'avatarUrl' => $object->getAvatarUrl() ? $this->filesystem->publicUrl($object->getAvatarUrl()) : null
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

    /**
     * @throws \Exception
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): User
    {
        $user = new User(
            $data['user_id'],
            trim($data['first_name']),
            trim($data['last_name']),
            $data['email'],
            $data['password'],
            trim($data['phone_number_prefix']),
            trim($data['phone_number']),
            trim($data['description']),
            new \DateTime($data['creation_date']),
            ['ROLE_USER']
        );
        $user->setAvatarUrl($data['avatar_path']);
        $notificationKeys = ['event_notification', 'group_add_notification', 'group_remove_notification'];
        foreach ($notificationKeys as $notificationKey) {
            $user->getNotificationSettings()->addSetting(new NotificationSetting($notificationKey, $data[$notificationKey]));
        }
        return $user;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type == User::class;
    }
}