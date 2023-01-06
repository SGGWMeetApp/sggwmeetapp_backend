<?php

namespace App\Serializer;

use App\Model\AccountData;
use App\Model\NotificationSetting;
use App\Model\PhoneNumber;
use App\Model\UserData;
use App\Model\UserNotificationSettings;
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
            'email' => $object->getAccountData()->getEmail(),
            'firstName' => $object->getUserData()->getFirstName(),
            'lastName' => $object->getUserData()->getLastName(),
            'phoneNumberPrefix' => $object->getUserData()->getPhoneNumber()->getPrefix(),
            'phoneNumber' => $object->getUserData()->getPhoneNumber()->getNumber(),
            'description' => $object->getUserData()->getDescription(),
            'registrationDate' => $object->getRegistrationDate()->format('Y-m-d\TH:i:s.v\Z'),
            'avatarUrl' => $object->getUserData()->getAvatarUrl() ? $this->filesystem->publicUrl($object->getUserData()->getAvatarUrl()) : null
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
            new UserData(
                trim($data['first_name']),
                trim($data['last_name']),
                trim($data['description']),
                new PhoneNumber(
                    trim($data['phone_number_prefix']),
                    trim($data['phone_number']),
                )
            ),
            new AccountData(
                $data['email'],
                $data['password'],
                ['ROLE_USER']
            ),
            new \DateTime($data['creation_date'])
        );
        $user->getUserData()->setAvatarUrl($data['avatar_path']);
        foreach (UserNotificationSettings::NOTIFICATION_NAMES as $notificationKey) {
            $user->getNotificationSettings()->addSetting(new NotificationSetting($notificationKey, $data[$notificationKey]));
        }
        return $user;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type == User::class;
    }
}