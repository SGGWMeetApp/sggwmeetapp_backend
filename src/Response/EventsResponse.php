<?php

namespace App\Response;

use App\Model\PublicEvent;
use App\Serializer\AuthorUserNormalizer;
use App\Serializer\PlaceNormalizer;
use App\Serializer\PublicEventNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class EventsResponse extends JsonResponse
{

    /**
     * @throws SerializerExceptionInterface
     */
    public function __construct(?string $collectionName, PublicEvent ...$publicEvents)
    {
        if ($collectionName === null) {
            parent::__construct($this->responseData($publicEvents));
        } else {
            parent::__construct([$collectionName => $this->responseData($publicEvents)]);
        }
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function responseData(array $publicEvents): array
    {
        $normalizedEvents = [];
        $publicEventNormalizer = new PublicEventNormalizer();
        $authorNormalizer = new AuthorUserNormalizer();
        $locationNormalizer = new PlaceNormalizer();
        foreach($publicEvents as $event) {
            $normalizedEvent = $publicEventNormalizer->normalize($event);
            $authorData = $authorNormalizer->normalize($event->getAuthor());
            $locationData=$locationNormalizer->normalize($event->getLocation());
            $normalizedEvents [] = [
                ...$normalizedEvent,
                'locationData' => $locationData,
                'author' => $authorData
            ];
        }
        return $normalizedEvents;
    }
}