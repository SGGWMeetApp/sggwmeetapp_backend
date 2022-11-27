<?php

namespace App\Response;

use App\Model\PrivateEvent;
use App\Serializer\PrivateEventNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;

class PrivateEventResponse extends JsonResponse
{

    /**
     * PrivateEventResponse constructor
     * @param PrivateEvent $privateEvent
     */
    public function __construct(PrivateEvent $privateEvent)
    {
        parent::__construct($this->responseData($privateEvent));
    }

    public function responseData(PrivateEvent $privateEvent): array
    {
        $privateEventNormalizer = new PrivateEventNormalizer();
        $privateEventData = $privateEventNormalizer->normalize($privateEvent);
        $eventAuthor = $privateEvent->getAuthor();
        $authorData = [
            'firstName' => $eventAuthor->getFirstName(),
            'lastName' => $eventAuthor->getLastName(),
            'email' => $eventAuthor->getUserIdentifier()
        ];
        return [
            ...$privateEventData,
            "author" => $authorData
        ];
    }
}