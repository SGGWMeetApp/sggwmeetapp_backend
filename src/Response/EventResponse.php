<?php

namespace App\Response;

use App\Factory\NormalizerFactory;
use App\Model\Event;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class EventResponse extends JsonResponse
{
    private NormalizerFactory $normalizerFactory;

    /**
     * EventResponse constructor
     * @param Event $event
     * @param NormalizerFactory $normalizerFactory
     * @throws SerializerExceptionInterface
     */
    public function __construct(Event $event, NormalizerFactory $normalizerFactory)
    {
        $this->normalizerFactory = $normalizerFactory;
        parent::__construct($this->responseData($event));
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function responseData(Event $event): array
    {
        return $this->normalizerFactory->getNormalizer($event)->normalize($event);
    }
}