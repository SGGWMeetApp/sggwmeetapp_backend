<?php

namespace App\Controller;

use App\Exception\FormException;
use App\Form\PrivateEventType;
use App\Factory\NormalizerFactory;
use App\Form\UserGroupDataType;
use App\Model\PrivateEvent;
use App\Model\UserGroup;
use App\Repository\PlaceRepositoryInterface;
use App\Repository\EventRepositoryInterface;
use App\Repository\UniqueConstraintViolationException;
use App\Repository\UserRepositoryInterface;
use App\Repository\EntityNotFoundException;
use App\Repository\UserGroupRepositoryInterface;
use App\Request\CreateUserGroupRequest;
use App\Request\PrivateEventRequest;
use App\Response\GroupUsersResponse;
use App\Response\GroupsResponse;
use App\Response\EventsResponse;
use App\Response\EventResponse;
use App\Security\User;
use App\Serializer\UserNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;


class UserGroupController extends ApiController
{
    private UserRepositoryInterface $userRepository;
    private UserGroupRepositoryInterface $userGroupRepository;
    private EventRepositoryInterface $eventRepository;
    private PlaceRepositoryInterface $placeRepository;
    private NormalizerFactory $normalizerFactory;

    public function __construct(
        UserRepositoryInterface         $userRepository,
        UserGroupRepositoryInterface    $userGroupRepository,
        EventRepositoryInterface        $eventRepository,
        PlaceRepositoryInterface        $placeRepository,
        NormalizerFactory               $normalizerFactory
    )
    {
        $this->userRepository = $userRepository;
        $this->userGroupRepository = $userGroupRepository;
        $this->eventRepository = $eventRepository;
        $this->placeRepository = $placeRepository;
        $this->normalizerFactory = $normalizerFactory;
    }


    /**
     * @throws SerializerExceptionInterface
     */
    public function getGroupPrivateEvents(int $group_id): JsonResponse
    {
        $jwtUser = $this->getUser();
        try {
            $user = $this->userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $userGroup = $this->userGroupRepository->findOrFail($group_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        if(!$userGroup->containsUser($user)) {
            return $this->respondUnauthorized('Unauthorized. You are not a member of this group.');
        }

        $privateEvents = $this->eventRepository->findAllForGroup($userGroup);
        return new EventsResponse('events', $this->normalizerFactory, ...$privateEvents);
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function createGroupPrivateEvent(int $group_id, Request $request): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $addPrivateEventRequest = new PrivateEventRequest();
        $this->handlePrivateEventRequest($addPrivateEventRequest, $requestData);

        $jwtUser = $this->getUser();
        try {
            $user = $this->userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $userGroup = $this->userGroupRepository->findOrFail($group_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        $isUserGroupAdmin = $userGroup->containsUser($user) && $userGroup->getOwner()->isEqualTo($user);
        if(!$isUserGroupAdmin) {
            return $this->respondUnauthorized('Unauthorized. You are not authorized to create events in this group.');
        }

        if($addPrivateEventRequest->publicEventId !== null) {
            return $this->createGroupEventFromPublicEvent($addPrivateEventRequest->publicEventId, $userGroup);
        } else {
            return $this->createGroupEventFromScratch($addPrivateEventRequest, $user, $userGroup);
        }
    }

    /**
     * @throws SerializerExceptionInterface
     */
    private function createGroupEventFromPublicEvent(int $publicEventId, UserGroup $userGroup): JsonResponse
    {
        try {
            $publicEvent = $this->eventRepository->findOrFail($publicEventId);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound('Public event not found.');
        }
        $privateEvent = $publicEvent->convertToPrivateEvent($userGroup);
        $this->eventRepository->add($privateEvent);
        $userGroup->addEvent($privateEvent);

        return new EventResponse($privateEvent, $this->normalizerFactory);
    }

    /**
     * @throws SerializerExceptionInterface
     */
    private function createGroupEventFromScratch(
        PrivateEventRequest $addPrivateEventRequest,
        User $eventAuthor,
        UserGroup $userGroup
    ): JsonResponse
    {
        try {
            $location = $this->placeRepository->findOrFail($addPrivateEventRequest->locationId);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound('Location not found.');
        }
        $privateEvent = new PrivateEvent(
            null,
            $addPrivateEventRequest->name,
            $location,
            $addPrivateEventRequest->description,
            $addPrivateEventRequest->startDate,
            $eventAuthor,
            $userGroup
        );
        $this->eventRepository->add($privateEvent);
        $userGroup->addEvent($privateEvent);

        return new EventResponse($privateEvent, $this->normalizerFactory);
    }

    private function handlePrivateEventRequest(PrivateEventRequest $request, mixed $requestData): void
    {
        $form = $this->createForm(PrivateEventType::class, $request);
        $form->submit($requestData);
        if (!$form->isValid()) {
            throw new FormException($form);
        }
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function enableGroupEventNotifications(Request $request, int $group_id, int $event_id): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $enableNotification = $requestData["enable24hNotification"];

        $jwtUser = $this->getUser();
        try {
            $user = $this->userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $userGroup = $this->userGroupRepository->findOrFail($group_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }

        $isUserInGroup = $userGroup->containsUser($user);
        if(!$isUserInGroup) {
            return $this->respondUnauthorized('Unauthorized. You are not a member of this group.');
        }

        try {
             $privateEvent = $this->eventRepository->findOrFail($event_id);
        } catch(EntityNotFoundException) {
            return $this->respondNotFound();
        }

        if(!$privateEvent instanceof PrivateEvent) {
            if(!$privateEvent->getUserGroup()->isEqualTo($userGroup)) {
                return $this->respondNotFound();
            }
        }

        $privateEvent->setNotificationsEnabled($enableNotification);
        $this->eventRepository->update($privateEvent);

        return new EventResponse($privateEvent, $this->normalizerFactory);
    }

    public function createGroup(
        Request $request
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $userGroupRequest = new CreateUserGroupRequest();
        $this->handleCreateGroupRequest($userGroupRequest, $requestData);
        $jwtUser = $this->getUser();
        try {
            $user = $this->userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }

        $userGroup = new UserGroup(null, $userGroupRequest->name, $user);
        $userGroup->addUser($user);

        try {
            $this->userGroupRepository->add($userGroup);
        } catch(UniqueConstraintViolationException $e) {
            return $this->respondWithError('BAD_REQUEST', $e->getMessage());
        }

        return $this->response([
            "id" => $userGroup->getGroupId(),
            "name" => $userGroup->getName()
        ]);
    }

    private function handleCreateGroupRequest(CreateUserGroupRequest $request, mixed $requestData): void
    {
        $form = $this->createForm(UserGroupDataType::class, $request);
        $form->submit($requestData);
        if (!$form->isValid()) {
            throw new FormException($form);
        }
    }

    public function getGroups(): JsonResponse
    {
        $jwtUser = $this->getUser();
        try {
            $user = $this->userRepository->findOrFail($jwtUser->getUserIdentifier());
            $userGroups = $this->userGroupRepository->findAll();
            foreach($userGroups as $userGroup) {
                $events = $this->eventRepository->findAllForGroup($userGroup);
                $userGroup->setEvents($events);
            }
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            return new GroupsResponse($userGroups, $user, $this->normalizerFactory);
        } catch (SerializerExceptionInterface $e) {
            return $this->respondInternalServerError($e);
        }
    }

    public function getGroupUsers(int $group_id): JsonResponse
    {
        $jwtUser = $this->getUser();
        try {
            $user = $this->userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $userGroup = $this->userGroupRepository->findOrFail($group_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }

        $isUserInGroup = $userGroup->containsUser($user);
        if(!$isUserInGroup) {
            return $this->respondUnauthorized('Unauthorized. You are not a member of this group.');
        }
        try {
            return new GroupUsersResponse($userGroup, $user, $this->normalizerFactory);
        } catch (SerializerExceptionInterface $e) {
            return $this->respondInternalServerError($e);
        }
    }

    public function addGroupUser(Request $request, int $group_id): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $userId = $requestData["userId"];

        $jwtUser = $this->getUser();
        try {
            $currentUser = $this->userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $user = $this->userRepository->findByIdOrFail($userId);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound('User with given id does not exist.');
        }
        try {
            $userGroup = $this->userGroupRepository->findOrFail($group_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound('Group with given id does not exist.');
        }

        $isUserInGroup = $userGroup->containsUser($currentUser);
        if(!$isUserInGroup) {
            return $this->respondUnauthorized('Unauthorized. You are not a member of this group.');
        }

        $userGroup->addUser($user);
        $user->addGroup($userGroup);

        try {
            $this->userGroupRepository->addGroupUser($userGroup, $user);
        } catch (UniqueConstraintViolationException $e) {
                return match ($e->getViolatedConstraint()) {
                    'users_user_groups_pkey' =>
                    $this->setStatusCode(409)
                        ->respondWithError('INVALID_ENTITY', 'User is already in this group.'),
                default => $this->setStatusCode(409)
                    ->respondWithError('INVALID_ENTITY', $e->getMessage()),
            };
        }

        return $this->response([
            ...$this->normalizerFactory->getNormalizer($user)->normalize($user, 'json', [
                'modelProperties' => UserNormalizer::AUTHOR_PROPERTIES
            ]),
            "isAdmin" => false
        ]);
    }

    public function leaveGroup(int $group_id):JsonResponse
    {
        $jwtUser = $this->getUser();
        try {
            $user = $this->userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $userGroup = $this->userGroupRepository->findOrFail($group_id);
        } catch (EntityNotFoundException $e) {
            return $this->respondNotFound();
        }

        // when owner leaves the group, a group is deleted (users are deleted on cascade)
        if($user->isEqualTo($userGroup->getOwner())) {
            $this->userGroupRepository->delete($userGroup);
        } else {
            $this->userGroupRepository->deleteUserFromGroup($userGroup->getGroupId(), $user->getId());
        }

        $userGroups = $this->userGroupRepository->findAllGroupsForUser($user->getId());

        try {
            return new GroupsResponse($userGroups, $user, $this->normalizerFactory);
        } catch (SerializerExceptionInterface $e) {
            return $this->respondInternalServerError($e);
        }
    }

    public function leaveGroupEvent(int $event_id): JsonResponse
    {
        return $this->setStatusCode(204)->response([]);
    }


}
