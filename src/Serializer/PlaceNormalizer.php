<?php

namespace App\Serializer;

use App\Model\GeoLocation;
use App\Model\Place;
use League\Flysystem\Filesystem;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PlaceNormalizer implements NormalizerInterface, DenormalizerInterface
{
    private Filesystem $filesystem;
    public const LOCATION_PROPERTIES = ['id','name'];

    public function __construct(Filesystem $uploadsFilesystem)
    {
        $this->filesystem = $uploadsFilesystem;
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function normalize(mixed $object, string $format = null, array $context = []): float|int|bool|\ArrayObject|array|string|null
    {
        if (!$object instanceof Place) {
            throw new InvalidArgumentException('This normalizer only accepts objects of type App\Model\Place');
        }
        $publicPhotoPaths = [];
        foreach($object->getPhotoPaths() as $path) {
            $publicPhotoPaths [] = $this->filesystem->publicUrl($path);
        }

        $normalizedLocation = [
            "id" => $object->getId(),
            "name" => $object->getName(),
            "geolocation" => [
                "latitude" => $object->getGeoLocation()->getLatitude(),
                "longitude" => $object->getGeoLocation()->getLongitude()
            ],
            "textLocation" => $object->getTextLocation(),
            "description" => $object->getDescription(),
            "locationCategoryCodes" => $object->getCategoryCodes(),
            "photoPath" => count($publicPhotoPaths) > 0 ? $publicPhotoPaths[0] : null,
            "menuPath" => $object->getMenuPath() !== null ? $this->filesystem->publicUrl($object->getMenuPath()): null
        ];

        if (array_key_exists('modelProperties', $context) && is_array($context['modelProperties'])) {
            $locationProperties = [];
            try {
                foreach ($context['modelProperties'] as $modelProperty) {
                    $locationProperties [$modelProperty] = $normalizedLocation[$modelProperty];
                }
                return $locationProperties;
            } catch (\OutOfBoundsException) {
                throw new \Exception("One or more properties do not exist for location model in PlaceNormalizer normalize() method.");
            }
        }

        return $normalizedLocation;
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
            $data['text_location'],
            $data['description'],
            $data['rating_pct']
        );
        $categories = json_decode($data['category_names'], true);
        foreach ($categories as $category) {
            $place->addCategoryCode($category);
        }
        $photoPaths = json_decode($data['photo_paths'], true);
        foreach ($photoPaths as $path) {
            $place->addPhotoPath($path);
        }
        $place->setMenuPath($data['menu_path']);
        $place->setReviewsCount($data['reviews_count']);
        return $place;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return is_array($data) && $type == 'Place';
    }
}