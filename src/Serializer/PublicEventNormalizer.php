<?php

namespace App\Serializer;

use App\Model\GeoLocation;
use App\Model\PublicEvent;
use App\Model\Place;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Monolog\DateTimeImmutable;
use App\Security\User;

class PublicEventNormalizer implements NormalizerInterface, DenormalizerInterface
{

    /**
     * @inheritDoc
     */
    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        if (!$object instanceof PublicEvent) {
            throw new InvalidArgumentException('This normalizer only accepts objects of type App\Model\PublicEvent');
        }
        return [
            "id" => $object->getId(),
            "name" => $object->getName(),
            "description" => $object->getDescription(),
            "locationData" => $object->getLocation(),
            "startDate" => $object->getStartDate()->format('Y-m-d\TH:i:s.v\Z'),
            "author" => $object->getAuthor(),
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

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): PublicEvent
    {
        
        $publicEvent = new PublicEvent(
            (int)$data['event_id'],
            $data['eventname'],
            new Place(
                (int)$data['location_id'],
                $data['locname'],
                new GeoLocation(
                    $data['lat'],
                    $data['long'],
                ),
                $data['locdes'],
                $data['rating_pct']
            ),
           
            $data['evntdes'],
            new DateTimeImmutable($data['start_date']),
            new User(
                (int)$data['user_id'],
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                "TRZYMAJ JEZYK ZA ZĘBAMI",
                $data['phone_number_prefix'],
                $data['phone_number'],
                $data['userdes'],
                ['ROLE_USER']
            ),
            $data['can_edit']
        );
        
      
        return $publicEvent ;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return is_array($data) && $type == 'PublicEvent';
    }
}