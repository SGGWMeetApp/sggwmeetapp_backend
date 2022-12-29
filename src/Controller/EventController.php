<?php

namespace App\Controller;

use App\Factory\NormalizerFactory;
use App\Model\PrivateEvent;
use App\Repository\EntityNotFoundException;
use App\Response\EventsResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Model\PublicEvent;
use App\Repository\EventRepositoryInterface;
use App\Repository\UniqueConstraintViolationException;
use App\Repository\UserRepositoryInterface;
use App\Repository\PlaceRepositoryInterface;
use App\Request\PublicEventRequest;
use App\Response\EventResponse;
use App\Form\PublicEventType;
use App\Exception\FormException;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class EventController extends ApiController {

    private UserRepositoryInterface $userRepository;
    private EventRepositoryInterface $eventRepository;
    private PlaceRepositoryInterface $placeRepository;
    private NormalizerFactory $normalizerFactory;

    public function __construct(
        UserRepositoryInterface         $userRepository,
        EventRepositoryInterface        $eventRepository,
        PlaceRepositoryInterface        $placeRepository,
        NormalizerFactory               $normalizerFactory
    )
    {
        $this->userRepository = $userRepository;
        $this->eventRepository = $eventRepository;
        $this->placeRepository = $placeRepository;
        $this->normalizerFactory = $normalizerFactory;
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function getPublicEvents(): JsonResponse
    {
        try {
            $events= $this->eventRepository->findAllPublicEvents();
        } catch (\Throwable $e) {
            return $this->respondInternalServerError($e);
        }
        return new EventsResponse('events', $this->normalizerFactory, ...$events);
    }

    public function createPublicEvent(Request $request): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $addPublicEventRequest = new PublicEventRequest();
        $this->handlePublicEventRequest($addPublicEventRequest,$requestData);
        
        $jwtUser = $this->getUser();
        try {
            $user = $this->userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $location = $this->placeRepository->findOrFail($addPublicEventRequest->locationId);
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
            $this->eventRepository->add($publicEvent);
        } catch (UniqueConstraintViolationException $e) {
            return $this->setStatusCode(409)->respondWithError('BAD_REQUEST', $e->getMessage());
        }
        try {
            return new EventResponse($publicEvent, $this->normalizerFactory);
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


    public function updateEvent(Request $request, int $event_id): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $updatePublicEventRequest = new PublicEventRequest();
        $this->handlePublicEventRequest($updatePublicEventRequest,$requestData);

        $jwtUser = $this->getUser();
        try {
            $user = $this->userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
             $publicEvent = $this->eventRepository->findOrFail($event_id);
             if(!($publicEvent->getCanEdit())){
                return $this->setStatusCode(409)
                    ->respondWithError('Can_edit=False', 'Event is not editable.');
             }
             if(!$publicEvent->getAuthor()->isEqualTo($user)) {
                 return $this->respondUnauthorized();
             }
        } catch (EntityNotFoundException) {
             return $this->respondNotFound('Event not found.');
        }
        try {
            $location = $this->placeRepository->findOrFail($updatePublicEventRequest->locationId);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound('Location not found.');
        }

        $publicEvent->setName($updatePublicEventRequest->name);
        $publicEvent->setLocation($location);
        $publicEvent->setDescription($updatePublicEventRequest->description);
        $publicEvent->setStartDate($updatePublicEventRequest->startDate);
        try {
            $this->eventRepository->update($publicEvent);
        } catch (UniqueConstraintViolationException $e) {
            return $this->setStatusCode(409)->respondWithError('BAD_REQUEST', $e->getMessage());
        }
        try {
            return new EventResponse($publicEvent, $this->normalizerFactory);
        } catch (SerializerExceptionInterface $e) {
            return $this->respondInternalServerError($e);
        }
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function getUpcomingEvents(): JsonResponse
    {
        try {
            $events = $this->eventRepository->findUpcomingPublicEvents();
        } catch (\Throwable $e) {
            return $this->respondInternalServerError($e);
        }
        return new EventsResponse('events', $this->normalizerFactory,  ...$events);
    }

    public function joinEvent(int $event_id, int $user_id): JsonResponse
    {
        $jwtUser = $this->getUser();
        try {
            $currentUser = $this->userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        if($currentUser->getId() !== $user_id) {
            return $this->respondUnauthorized();
        }
        try {
            $event = $this->eventRepository->findOrFail($event_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound('Event not found.');
        }
        if ($event instanceof PrivateEvent) {
            $userGroup = $event->getUserGroup();
            if (!$userGroup->containsUser($currentUser)) {
                return $this->respondUnauthorized();
            }
        }
        try {
            $this->eventRepository->addUserToEventAttenders($currentUser, $event);
        } catch (UniqueConstraintViolationException) {
            return $this->setStatusCode(400)->respondWithError(
                'ALREADY_REGISTERED',
                'This user is already an attender of this event.'
            );
        } catch (\Throwable $e) {
            return $this->respondInternalServerError($e);
        }
        return $this->respondWithSuccessMessage('Successfully joined event.');
    }

    public function leaveEvent(int $event_id, int $user_id): JsonResponse
    {
        $jwtUser = $this->getUser();
        try {
            $currentUser = $this->userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        if($currentUser->getId() !== $user_id) {
            return $this->respondUnauthorized();
        }
        try {
            $event = $this->eventRepository->findOrFail($event_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound('Event not found.');
        }
        try {
            $this->eventRepository->removeUserFromEventAttenders($currentUser, $event);
        } catch (\Throwable $e) {
            return $this->respondInternalServerError($e);
        }
        return $this->setStatusCode(204)->response([]);
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function getUserEvents(int $user_id): JsonResponse
    {
        $jwtUser = $this->getUser();
        try {
            $currentUser = $this->userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        if($currentUser->getId() !== $user_id) {
            return $this->respondUnauthorized();
        }
        $userEvents = $this->eventRepository->findAllForUser($currentUser);
        return new EventsResponse('events', $this->normalizerFactory,  ...$userEvents);
    }

}