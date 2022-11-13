<?php

namespace App\Serializer;

use App\Model\GeoLocation;
use App\Model\PublicEvent;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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
            "description" =>$object->getDescription(),
            "geolocation" => [
                "latitude" => $object->getGeoLocation()->getLatitude(),
                "longitude" => $object->getGeoLocation()->getLongitude()
            ],
            "startDate" => $object->getStartDate(),
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
            $data['location_id'],
            $data['name'],
            new GeoLocation($data['lat'], $data['long']),
            $data['description'],
            $data['startDate'],
            $data['author'],
            $data['canEdit']
        );
        
        return $publicEvent ;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return is_array($data) && $type == 'PublicEvent';
    }
}