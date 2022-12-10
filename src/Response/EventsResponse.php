<?php

namespace App\Response;

use App\Factory\NormalizerFactory;
use App\Model\Event;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class EventsResponse extends JsonResponse
{
    private NormalizerFactory $normalizerFactory;
    /**
     * @throws SerializerExceptionInterface
     */
    public function __construct(
        ?string                 $collectionName,
        NormalizerFactory       $normalizerFactory,
        Event                   ...$events
    )
    {
        $this->normalizerFactory = $normalizerFactory;
        if ($collectionName === null) {
            parent::__construct($this->responseData($events));
        } else {
            parent::__construct([$collectionName => $this->responseData($events)]);
        }
    }

    /**
     * @throws SerializerExceptionInterface
     */
    private function responseData(array $events): array
    {
        if (count($events) < 1) {
            return [];
        }
        $eventNormalizer = $this->normalizerFactory->getNormalizer($events[0]);
        $normalizedEvents = [];
        foreach($events as $event) {
            $normalizedEvents [] = $eventNormalizer->normalize($event);
        }
        return $normalizedEvents;
    }
}