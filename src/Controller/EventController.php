<?php

namespace App\Controller;

use App\Factory\NormalizerFactory;
use App\Repository\EntityNotFoundException;
use App\Response\PublicEventsResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Model\PublicEvent;
use App\Repository\PublicEventRepositoryInterface;
use App\Repository\UniqueConstraintViolationException;
use App\Repository\UserRepositoryInterface;
use App\Repository\PlaceRepositoryInterface;
use App\Request\PublicEventRequest;
use App\Response\PublicEventResponse;
use App\Form\PublicEventType;
use App\Exception\FormException;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class EventController extends ApiController {

    /**
     * @throws SerializerExceptionInterface
     */
    public function getPublicEventsAction(
        PublicEventRepositoryInterface $publicEventRepository,
        NormalizerFactory $normalizerFactory
    ): JsonResponse
    {
        try {
            $events=$publicEventRepository->findAll();
        } catch (\Throwable $e) {
            return $this->respondInternalServerError($e);
        }
        return new PublicEventsResponse('publicEvents', $normalizerFactory, ...$events);
    }

    public function createPublicEvent(
        Request $request,
        UserRepositoryInterface $userRepository,
        PublicEventRepositoryInterface $publicEventRepository,  
        PlaceRepositoryInterface $placeRepository,
        NormalizerFactory $normalizerFactory
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $addPublicEventRequest = new PublicEventRequest();
        $this->handlePublicEventRequest($addPublicEventRequest,$requestData);
        
        $jwtUser = $this->getUser();
        try {
            $user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $location = $placeRepository->findOrFail($addPublicEventRequest->locationId);
       } catch (EntityNotFoundException) {
            return $this->respondNotFound();
       }
        $publicEvent = new PublicEvent(
            null,
            $addPublicEventRequest->name,
            $location,
            $addPublicEventRequest->description,
            $addPublicEventRequest->startDate,
            $user
        );
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
        try {
            return new PublicEventResponse($publicEvent, $normalizerFactory);
        } catch (SerializerExceptionInterface $e) {
            return $this->respondInternalServerError($e);
        }
    }

    private function handlePublicEventRequest(PublicEventRequest $request, mixed $requestData): void
    {
        $form = $this->createForm(PublicEventType::class, $request);
        $form->submit($requestData);
        if (!$form->isValid()) {
            throw new FormException($form);
        }
    }


    public function updateEvent(
        Request                         $request,
        int                             $event_id,
        PublicEventRepositoryInterface  $publicEventRepository,
        UserRepositoryInterface         $userRepository,
        PlaceRepositoryInterface        $placeRepository,
        NormalizerFactory               $normalizerFactory
    ): JsonResponse 
    {
        $requestData = json_decode($request->getContent(),true);
        $updatePublicEventRequest = new PublicEventRequest();
        $this->handlePublicEventRequest($updatePublicEventRequest,$requestData);

        $jwtUser = $this->getUser();
        try {
            $user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
             $publicEvent = $publicEventRepository->findOrFail($event_id);
             if(!($publicEvent->getCanEdit())){
                return $this->setStatusCode(409)
                ->respondWithError('Can_edit=False', 'Event nie ma możliwości edycji.');
             }
             if(!$publicEvent->getAuthor()->isEqualTo($user)) {
                 return $this->respondUnauthorized();
             }
        } catch (EntityNotFoundException) {
             return $this->respondNotFound('Event not found.');
        }
        try {
            $location = $placeRepository->findOrFail($updatePublicEventRequest->locationId);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound('Location not found.');
        }

        $publicEvent->setName($updatePublicEventRequest->name);
        $publicEvent->setLocation($location);
        $publicEvent->setDescription($updatePublicEventRequest->description);
        $publicEvent->setStartDate($updatePublicEventRequest->startDate);
        try {
            $publicEventRepository->update($publicEvent);
        } catch (UniqueConstraintViolationException $e) {
            return match ($e->getViolatedConstraint()) {
                'rating_unq_inx' => $this->setStatusCode(409)
                    ->respondWithError('BAD_REQUEST', 'Nie wiem co wpisac na razie.'),
                default => $this->setStatusCode(409)
                    ->respondWithError('BAD_REQUEST', $e->getMessage()),
            };
        }
        try {
            return new PublicEventResponse($publicEvent, $normalizerFactory);
        } catch (SerializerExceptionInterface $e) {
            return $this->respondInternalServerError($e);
        }
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function getUpcomingEventsAction(
        PublicEventRepositoryInterface  $publicEventRepository,
        NormalizerFactory $normalizerFactory
    ): JsonResponse 
    {
        try {
            $events = $publicEventRepository->findUpcoming();
        } catch (\Throwable $e) {
            return $this->respondInternalServerError($e);
        }
        return new PublicEventsResponse('publicEvents', $normalizerFactory,  ...$events);
    }

}