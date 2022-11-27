<?php

namespace App\Response;

use App\Factory\NormalizerFactory;
use App\Model\PublicEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class PublicEventResponse extends JsonResponse
{
    private NormalizerFactory $normalizerFactory;

    /**
     * PublicEventResponse constructor
     * @param PublicEvent $publicEvent
     * @param NormalizerFactory $normalizerFactory
     * @throws SerializerExceptionInterface
     */
    public function __construct(
        PublicEvent $publicEvent,
        NormalizerFactory $normalizerFactory
    )
    {
        $this->normalizerFactory = $normalizerFactory;
        parent::__construct($this->responseData($publicEvent));
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function responseData(PublicEvent $publicEvent): array
    {
        $publicEventData = $this->normalizerFactory->getNormalizer($publicEvent)->normalize($publicEvent);
        $authorData = $this->normalizerFactory->getNormalizer($publicEvent->getAuthor())->normalize($publicEvent->getAuthor());
        $locationData= $this->normalizerFactory->getNormalizer($publicEvent->getLocation())->normalize($publicEvent->getLocation());
        return [
            ...$publicEventData,
            "locationData" => $locationData,
            "author" => $authorData
        ];
    }
}