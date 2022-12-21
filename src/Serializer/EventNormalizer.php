<?php

namespace App\Serializer;

use App\Model\Event;
use App\Model\GeoLocation;
use App\Model\PrivateEvent;
use App\Model\PublicEvent;
use App\Model\Place;
use App\Model\UserGroup;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use App\Security\User;

class EventNormalizer implements NormalizerInterface, DenormalizerInterface
{
    private UserNormalizer $authorNormalizer;
    private PlaceNormalizer $placeNormalizer;

    public function __construct(UserNormalizer $authorNormalizer, PlaceNormalizer $placeNormalizer)
    {
        $this->authorNormalizer = $authorNormalizer;
        $this->placeNormalizer = $placeNormalizer;
    }

    /**
     * @inheritDoc
     */
    public function normalize(mixed $object, string $format = null, array $context = []): float|int|bool|\ArrayObject|array|string|null
    {
        if (!$object instanceof Event) {
            throw new InvalidArgumentException('This normalizer only accepts objects of type App\Model\Event');
        }
        return [
            'id' => $object->getId(),
            'name' => $object->getName(),
            'description' => $object->getDescription(),
            'locationData' => $this->placeNormalizer->normalize($object->getLocation(), 'json', [
                'modelProperties' => PlaceNormalizer::LOCATION_PROPERTIES
            ]),
            'startDate' => $object->getStartDate()->format('Y-m-d\TH:i:s.v\Z'),
            'author' => $this->authorNormalizer->normalize($object->getAuthor(), 'json', [
                'modelProperties' => UserNormalizer::AUTHOR_PROPERTIES
            ]),
            'canEdit' => $object->getCanEdit(),
            'notification24hEnabled' => $object->isNotificationsEnabled()
        ];
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Event;
    }

    /**
     * @throws \Exception
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): Event
    {
        $user = new User(
            $data['user_id'],
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            "",
            $data['phone_number_prefix'],
            $data['phone_number'],
            $data['userdes'],
            new \DateTime($data['userRegistrationDate']),
            ['ROLE_USER']
        );
        $user->setAvatarUrl($data['avatar_path']);
        $place = new Place(
            $data['location_id'],
            $data['locname'],
            new GeoLocation(
                $data['lat'],
                $data['long'],
            ),
            $data['text_location'],
            $data['locdes'],
            $data['rating_pct']
        );
        $categories = json_decode($data['category_names'], true);
        foreach ($categories as $category) {
            $place->addCategoryCode($category);
        }
        $photoPaths = json_decode($data['photo_paths'], true);
        foreach($photoPaths as $photoPath) {
            $place->addPhotoPath($photoPath);
        }

        if($data['is_public']) {
            return new PublicEvent(
                $data['event_id'],
                $data['eventname'],
                $place,
                $data['evntdes'],
                new \DateTimeImmutable($data['start_date']),
                $user,
                $data['can_edit'],
                $data['notification_enabled']
            );
        } else {
            return new PrivateEvent(
                $data['event_id'],
                $data['eventname'],
                $place,
                $data['evntdes'],
                new \DateTimeImmutable($data['start_date']),
                $user,
                new UserGroup(
                    $data['group_id'],
                    $data['group_name'],
                    new User(
                        $data['group_owner_id'],
                        $data['group_owner_first_name'],
                        $data['group_owner_last_name'],
                        $data['group_owner_email'],
                        '',
                        $data['group_owner_phone_number_prefix'],
                        $data['group_owner_phone_number'],
                        $data['group_owner_description'],
                        new \DateTime($data['userRegistrationDate']),
                        ['ROLE_USER']
                    )
                ),
                $data['can_edit'],
                $data['notification_enabled']
            );
        }


    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return is_array($data) && $type == 'Event';
    }
}