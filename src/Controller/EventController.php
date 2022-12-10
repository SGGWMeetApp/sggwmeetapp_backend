<?php

namespace App\Controller;

use App\Factory\NormalizerFactory;
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
            return match ($e->getViolatedConstraint()) {
                'rating_unq_inx' => $this->setStatusCode(409)
                    ->respondWithError('BAD_REQUEST', 'Nie wiem co wpisac na razie.'),
                default => $this->setStatusCode(409)
                    ->respondWithError('BAD_REQUEST', $e->getMessage()),
            };
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
                ->respondWithError('Can_edit=False', 'Event nie ma możliwości edycji.');
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
            return match ($e->getViolatedConstraint()) {
                'rating_unq_inx' => $this->setStatusCode(409)
                    ->respondWithError('BAD_REQUEST', 'Nie wiem co wpisac na razie.'),
                default => $this->setStatusCode(409)
                    ->respondWithError('BAD_REQUEST', $e->getMessage()),
            };
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

}