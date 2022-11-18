<?php

namespace App\Response;

use App\Model\PublicEvent;
use App\Serializer\AuthorUserNormalizer;
use App\Serializer\PublicEventNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;

class PublicEventResponse extends JsonResponse
{

    /**
     * PublicEventResponse constructor
     * @param PublicEvent $publicEvent
     */
    public function __construct(PublicEvent $publicEvent)
    {
        parent::__construct($this->responseData($publicEvent));
    }

    public function responseData(PublicEvent $publicEvent): array
    {
        $publicEventNormalizer = new PublicEventNormalizer();
        $publicEventData = $publicEventNormalizer->normalize($publicEvent);
        $authorNormalizer = new AuthorUserNormalizer();
        $authorData = $authorNormalizer->normalize($publicEvent->getAuthor());
        return [
            ...$publicEventData,
            "author" => $authorData
        ];
    }
}