<?php

namespace App\Controller;

use App\Repository\EntityNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Model\PublicEvent;
use App\Repository\PublicEventRepositoryInterface;
use App\Repository\UniqueConstraintViolationException;
use App\Repository\UserRepositoryInterface;
use App\Repository\PlaceRepositoryInterface;
use App\Request\PublicEventRequest;
use App\Response\PublicEventResponse;
use App\Serializer\PublicEventNormalizer;
use App\Form\PublicEventType;
use App\Exception\FormException;

class EventController extends ApiController {

    public function getPublicEventsAction(Request $request): JsonResponse {

        // 1. Get filters from request (converted to filters object)

        // 2. Get filtered events from database
        return $this->response(["events" => [
            [
                "id" => 1,
                "name" => "Planszówki",
                "description" => "Zapraszamy na świąteczną edycję planszówek! Wybierz jedną z setek gier i baw się razem z nami!",
                "startDate" => "2022-12-23T18:30:00.000Z",
                "locationData" => [
                    "name" => "Dziekanat 161"
                ],
                "author" => [
                    "firstName" => "Joanna",
                    "lastName" => "Nowak",
                    "email" => "joanna.nowak@email.com"
                ],
                "canEdit" => true,
                "notification24hEnabled" => true
            ],
            [
                "id" => 2,
                "name" => "Środowe Disco",
                "description" => "Już w tą środę widzimy się na parkiecie w Dziekanacie! Dobra zabawa gwarantowana! Do 22:00 bilet 10 zł, Po 22:00 15 zł.",
                "startDate" => "2022-11-06T21:00:00.000Z",
                "locationData" => [
                    "name" => "Dziekanat 161"
                ],
                "author" => [
                    "firstName" => "Jerzy",
                    "lastName" => "Dudek",
                    "email" => "jerzy.dudek@example.com"
                ],
                "canEdit" => false,
                "notification24hEnabled" => true
            ],
        ]]);
    }
    //nie zwraca dobrze autora w odp dlatego ta górna na szytwno 
    public function getPublicEvents(  Request $request,
    UserRepositoryInterface $userRepository,
    PublicEventRepositoryInterface $publicEventRepository,  
    ): JsonResponse 
    {
        $publicEvent = new PublicEventNormalizer();
            

        $jwtUser = $this->getUser();
        try {
            $user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $events=$publicEventRepository->findAll();
            
            $normalizedEvents = [];
            foreach($events as $event ) {
                
                $eventNormalizer = $publicEvent->normalize($event );
                
                $normalizedEvents [] = [
                    ...$eventNormalizer,
                    
                    
                ];
            }
        } catch (UniqueConstraintViolationException $e) {
            
            return match ($e->getViolatedConstraint()) {
                'rating_unq_inx' => $this->setStatusCode(409)
                    ->respondWithError('BAD_REQUEST', 'Nie wiem co wpisac na razie.'),
                default => $this->setStatusCode(409)
                    ->respondWithError('BAD_REQUEST', $e->getMessage()),
            };
        }
        //dd($normalizedEvents);
        return $this->response(['publicEvents' => $normalizedEvents]);
    }


    public function createPublicEvent(
        Request $request,
        UserRepositoryInterface $userRepository,
        PublicEventRepositoryInterface $publicEventRepository,  
        PlaceRepositoryInterface $placeRepository
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $addPublicEvent = new PublicEventRequest();
        $this->handlePublicEventRequest($addPublicEvent,$requestData);
        
        $jwtUser = $this->getUser();
        try {
            $user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $location = $placeRepository ->findOrFail($addPublicEvent->locationId);
       } catch (EntityNotFoundException) {
            return $this->respondNotFound();
       }
       

        $publicEvent = new PublicEvent(null, $addPublicEvent->name,$addPublicEvent->locationId,$location->getName(),$addPublicEvent->description, $addPublicEvent->startDate,$user);

        try {
            $publicEventRepository->add($publicEvent);
        } catch (UniqueConstraintViolationException $e) {
            
            return match ($e->getViolatedConstraint()) {
                'rating_unq_inx' => $this->setStatusCode(409)
                    ->respondWithError('BAD_REQUEST', 'Nie wiem co wpisac na razie.'),
                default => $this->setStatusCode(409)
                    ->respondWithError('BAD_REQUEST', $e->getMessage()),
            };
        }
        return new PublicEventResponse($publicEvent);
    }

    private function handlePublicEventRequest(PublicEventRequest $request, mixed $requestData): void
    {
        $form = $this->createForm(PublicEventType::class, $request);
        $form->submit($requestData);
        if (!$form->isValid()) {
            throw new FormException($form);
        }
    }


    public function updateEvent( Request $request, int $event_id,
        UserRepositoryInterface $userRepository,
        PublicEventRepositoryInterface $publicEventRepository   
    ): JsonResponse 
    {
        $requestData = json_decode($request->getContent(),true);
        $updataPublicEvent = new PublicEventRequest();
        $this->handlePublicEventRequest($updataPublicEvent,$requestData);

        $jwtUser = $this->getUser();
        try {
            $user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
             $publicEvent = $publicEventRepository ->findOrFail($event_id);
        } catch (EntityNotFoundException) {
             return $this->respondNotFound();
        }
        //dd($publicEvent->getLocationName());
        $newpublicEvent = new PublicEvent($event_id, $updataPublicEvent->name,$updataPublicEvent->locationId,$publicEvent->getLocationName(),$updataPublicEvent->description, $updataPublicEvent->startDate,$publicEvent->getAuthor());
        
        try {
            $publicEventRepository->update($newpublicEvent);
        } catch (UniqueConstraintViolationException $e) {
            
            return match ($e->getViolatedConstraint()) {
                'rating_unq_inx' => $this->setStatusCode(409)
                    ->respondWithError('BAD_REQUEST', 'Nie wiem co wpisac na razie.'),
                default => $this->setStatusCode(409)
                    ->respondWithError('BAD_REQUEST', $e->getMessage()),
            };
        }
        
        return new PublicEventResponse($newpublicEvent);
    }

    public function getUpcomingEventsAction(): JsonResponse {
        
        return $this->response(["events" => [
            [
                "id" => 2,
                "name" => "Środowe Disco",
                "description" => "Już w tą środę widzimy się na parkiecie w Dziekanacie! Dobra zabawa gwarantowana! Do 22:00 bilet 10 zł, Po 22:00 15 zł.",
                "startDate" => "2022-11-06T21:00:00.000Z",
                "locationData" => [
                    "name" => "Dziekanat 161"
                ],
                "author" => [
                    "firstName" => "Jerzy",
                    "lastName" => "Dudek",
                    "email" => "jerzy.dudek@example.com"
                ],
                "canEdit" => false,
                "notification24hEnabled" => true
            ],
        ]]);
    }

}