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
use App\Serializer\AuthorUserNormalizer;
use App\Serializer\PlaceNormalizer;
use App\Form\PublicEventType;
use App\Exception\FormException;

class EventController extends ApiController {

  
    public function getPublicEventsAction(  Request $request,
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
                $authorNormalizer = new AuthorUserNormalizer();
                $authorData = $authorNormalizer->normalize($event->getAuthor());
                $locationNormalizer = new PlaceNormalizer();
                $locationData=$locationNormalizer->normalize($event->getLocation());
                $normalizedEvents [] = [
                    ...$eventNormalizer,
                    "locationData" => $locationData,
                    "author" => $authorData
                    
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
            $location = $placeRepository ->findOrFail($requestData['locationId']);
       } catch (EntityNotFoundException) {
            return $this->respondNotFound();
       }
       
       //dd($location);
        $publicEvent = new PublicEvent(null, $addPublicEvent->name,$location,$addPublicEvent->description, $addPublicEvent->startDate,$user);
        
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
        //dd($requestData);
        $form->submit($requestData);
        if (!$form->isValid()) {
            throw new FormException($form);
        }
       
    }


    public function updateEvent( Request $request, int $event_id,
        UserRepositoryInterface $userRepository,
        PublicEventRepositoryInterface $publicEventRepository,
        PlaceRepositoryInterface $placeRepository   
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
             
             if(!($publicEvent->getCanEdit())){
                return $this->setStatusCode(409)
                ->respondWithError('Can_edit=False', 'Event nie ma możliwości edycji.');
             }
             
        } catch (EntityNotFoundException) {
             return $this->respondNotFound();
        }
        try {
           
            $location = $placeRepository ->findOrFail($requestData['locationId']);
            
       } catch (EntityNotFoundException) {
            return $this->respondNotFound();
       }


       
        //dd($publicEvent->getLocationName());
        $newpublicEvent = new PublicEvent($event_id, $updataPublicEvent->name,$location,$updataPublicEvent->description, $updataPublicEvent->startDate,$publicEvent->getAuthor());
        
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

    public function getUpcomingEventsAction(  Request $request,
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
            $events=$publicEventRepository->findUpcoming();
            
            $normalizedEvents = [];
            foreach($events as $event ) {
                
                $eventNormalizer = $publicEvent->normalize($event );
                $authorNormalizer = new AuthorUserNormalizer();
                $authorData = $authorNormalizer->normalize($event->getAuthor());
                $locationNormalizer = new PlaceNormalizer();
                $locationData=$locationNormalizer->normalize($event->getLocation());
                $normalizedEvents [] = [
                    ...$eventNormalizer,
                    "locationData" => $locationData,
                    "author" => $authorData
                    
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

}