<?php

namespace App\Response;

use App\Factory\NormalizerFactory;
use App\Model\PrivateEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class PrivateEventResponse extends JsonResponse
{
    private NormalizerFactory $normalizerFactory;

    /**
     * PrivateEventResponse constructor
     * @param PrivateEvent $privateEvent
     * @param NormalizerFactory $normalizerFactory
     * @throws SerializerExceptionInterface
     */
    public function __construct(PrivateEvent $privateEvent, NormalizerFactory $normalizerFactory)
    {
        $this->normalizerFactory = $normalizerFactory;
        parent::__construct($this->responseData($privateEvent));
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function responseData(PrivateEvent $privateEvent): array
    {
        $privateEventData = $this->normalizerFactory->getNormalizer($privateEvent)->normalize($privateEvent);
        $eventAuthor = $privateEvent->getAuthor();
        $authorData = $this->normalizerFactory->getNormalizer($eventAuthor)->normalize($eventAuthor);
        return [
            ...$privateEventData,
            "author" => $authorData
        ];
    }
}