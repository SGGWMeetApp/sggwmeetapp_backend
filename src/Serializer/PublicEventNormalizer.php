<?php

namespace App\Serializer;

use App\Model\GeoLocation;
use App\Model\PublicEvent;
use App\Model\Place;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use App\Security\User;

class PublicEventNormalizer implements NormalizerInterface, DenormalizerInterface
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
        if (!$object instanceof PublicEvent) {
            throw new InvalidArgumentException('This normalizer only accepts objects of type App\Model\PublicEvent');
        }
        return [
            "id" => $object->getId(),
            "name" => $object->getName(),
            "description" => $object->getDescription(),
            "locationData" => $this->placeNormalizer->normalize($object->getLocation()),
            "startDate" => $object->getStartDate()->format('Y-m-d\TH:i:s.v\Z'),
            "author" => $this->authorNormalizer->normalize($object->getAuthor(), 'json', [
                'modelProperties' => UserNormalizer::AUTHOR_PROPERTIES
            ]),
            "canEdit" => $object->getCanEdit(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof PublicEvent;
    }

    /**
     * @throws \Exception
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): PublicEvent
    {
        $user = new User(
            (int)$data['user_id'],
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            "TRZYMAJ JEZYK ZA ZĘBAMI",
            $data['phone_number_prefix'],
            $data['phone_number'],
            $data['userdes'],
            ['ROLE_USER']
        );
        $user->setAvatarUrl($data['avatar_path']);
        $place = new Place(
            (int)$data['location_id'],
            $data['locname'],
            new GeoLocation(
                $data['lat'],
                $data['long'],
            ),
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
        return new PublicEvent(
            (int)$data['event_id'],
            $data['eventname'],
            $place,
            $data['evntdes'],
            new \DateTimeImmutable($data['start_date']),
            $user,
            $data['can_edit']
        );
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return is_array($data) && $type == 'PublicEvent';
    }
}