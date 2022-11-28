<?php

namespace App\Response;

use App\Factory\NormalizerFactory;
use App\Model\PrivateEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class PrivateEventsResponse extends JsonResponse
{
    private NormalizerFactory $normalizerFactory;
    /**
     * @throws SerializerExceptionInterface
     */
    public function __construct(
        ?string $collectionName,
        NormalizerFactory       $normalizerFactory,
        PrivateEvent             ...$privateEvents
    )
    {
        $this->normalizerFactory = $normalizerFactory;
        if ($collectionName === null) {
            parent::__construct($this->responseData($privateEvents));
        } else {
            parent::__construct([$collectionName => $this->responseData($privateEvents)]);
        }
    }

    /**
     * @throws SerializerExceptionInterface
     */
    private function responseData(array $privateEvents): array
    {
        if (count($privateEvents) < 1) {
            return [];
        }
        $privateEventNormalizer = $this->normalizerFactory->getNormalizer($privateEvents[0]);
        $normalizedEvents = [];
        foreach($privateEvents as $privateEvent) {
            $normalizedEvents [] = $privateEventNormalizer->normalize($privateEvent);
        }
        return $normalizedEvents;
    }
}