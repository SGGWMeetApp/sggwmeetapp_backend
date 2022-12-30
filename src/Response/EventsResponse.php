<?php

namespace App\Response;

use App\Factory\NormalizerFactory;
use App\Model\Event;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class EventsResponse extends JsonResponse
{
    private NormalizerFactory $normalizerFactory;
    private array $userAttendance;

    /**
     * @throws SerializerExceptionInterface
     */
    public function __construct(
        ?string                 $collectionName,
        NormalizerFactory       $normalizerFactory,
        array                   $userAttendance,
        Event                   ...$events
    )
    {
        $this->normalizerFactory = $normalizerFactory;
        $this->userAttendance = $userAttendance;
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
            $attendance = $this->userAttendance[$event->getId()] ?? null;
            $normalizedEvents [] = [
                ...$eventNormalizer->normalize($event),
                'userAttends' => $attendance !== null ? $attendance['attends'] : false
            ];
        }
        return $normalizedEvents;
    }
}