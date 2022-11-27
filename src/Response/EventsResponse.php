<?php

namespace App\Response;

use App\Factory\NormalizerFactory;
use App\Model\PublicEvent;
use App\Serializer\UserNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class EventsResponse extends JsonResponse
{
    private NormalizerFactory $normalizerFactory;
    /**
     * @throws SerializerExceptionInterface
     */
    public function __construct(
        ?string $collectionName,
        NormalizerFactory       $normalizerFactory,
        PublicEvent             ...$publicEvents
    )
    {
        $this->normalizerFactory = $normalizerFactory;
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
        if(count($publicEvents) < 1) {
            return $normalizedEvents;
        }
        $firstEvent = $publicEvents[0];
        $publicEventNormalizer = $this->normalizerFactory->getNormalizer($firstEvent);
        $userNormalizer = $this->normalizerFactory->getNormalizer($firstEvent->getAuthor());
        $placeNormalizer = $this->normalizerFactory->getNormalizer($firstEvent->getLocation());
        foreach($publicEvents as $event) {
            $normalizedEvent = $publicEventNormalizer->normalize($event);
            $authorData = $userNormalizer->normalize($event->getAuthor(), 'json', [
                'modelProperties' => UserNormalizer::AUTHOR_PROPERTIES
            ]);
            $locationData=$placeNormalizer->normalize($event->getLocation());
            $normalizedEvents [] = [
                ...$normalizedEvent,
                'locationData' => $locationData,
                'author' => $authorData
            ];
        }
        return $normalizedEvents;
    }
}