<?php

namespace App\Serializer;

use App\Model\GeoLocation;
use App\Model\Place;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PlaceNormalizer implements NormalizerInterface, DenormalizerInterface
{

    /**
     * @inheritDoc
     */
    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        if (!$object instanceof Place) {
            throw new InvalidArgumentException('This normalizer only accepts objects of type App\Model\Place');
        }
        return [
            "id" => $object->getId(),
            "name" => $object->getName(),
            "geolocation" => [
                "latitude" => $object->getGeoLocation()->getLatitude(),
                "longitude" => $object->getGeoLocation()->getLongitude()
            ],
            "locationCategoryCodes" => $object->getCategoryCodes(),
            "photoPath" => "",
        ];
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Place;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): Place
    {
        $place = new Place(
            $data['location_id'],
            $data['name'],
            new GeoLocation($data['lat'], $data['long']),
            $data['description'],
            $data['rating_pct']
        );
        $categories = json_decode($data['category_names'], true);
        foreach ($categories as $category) {
            $place->addCategoryCode($category);
        }
        $place->setReviewsCount($data['reviews_count']);
        return $place;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return is_array($data) && $type == 'Place';
    }
}