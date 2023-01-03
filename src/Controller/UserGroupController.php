<?php

namespace App\Controller;

use App\Event\AddGroupEventEvent;
use App\Exception\FormException;
use App\Form\PrivateEventType;
use App\Factory\NormalizerFactory;
use App\Form\UserGroupDataType;
use App\Model\PrivateEvent;
use App\Model\PublicEvent;
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
use App\Service\SecurityHelper\JWTIdentityHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;


class UserGroupController extends ApiController
{
    private UserRepositoryInterface $userRepository;
    private UserGroupRepositoryInterface $userGroupRepository;
    private EventRepositoryInterface $eventRepository;
    private PlaceRepositoryInterface $placeRepository;
    private NormalizerFactory $normalizerFactory;
    private ?EventDispatcherInterface $eventDispatcher;

    public function __construct(
        UserRepositoryInterface         $userRepository,
        UserGroupRepositoryInterface    $userGroupRepository,
        EventRepositoryInterface        $eventRepository,
        PlaceRepositoryInterface        $placeRepository,
        NormalizerFactory               $normalizerFactory,
        ?EventDispatcherInterface       $eventDispatcher = null
    )
    {
        $this->userRepository = $userRepository;
        $this->userGroupRepository = $userGroupRepository;
        $this->eventRepository = $eventRepository;
        $this->placeRepository = $placeRepository;
        $this->normalizerFactory = $normalizerFactory;
        $this->eventDispatcher = $eventDispatcher;
    }


    /**
     * @throws SerializerExceptionInterface
     */
    public function getGroupPrivateEvents(int $group_id, JWTIdentityHelper $identityHelper): JsonResponse
    {
        $user = $identityHelper->getUser();
        try {
            $userGroup = $this->userGroupRepository->findOrFail($group_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        if(!$userGroup->containsUser($user)) {
            return $this->respondUnauthorized('Unauthorized. You are not a member of this group.');
        }

        $privateEvents = $this->eventRepository->findAllForGroup($userGroup);
        $userAttendance = $this->eventRepository->checkUserAttendance($identityHelper->getUser(), ...$privateEvents);
        return new EventsResponse('events', $this->normalizerFactory, $userAttendance, ...$privateEvents);
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function createGroupPrivateEvent(int $group_id, Request $request, JWTIdentityHelper $identityHelper): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $addPrivateEventRequest = new PrivateEventRequest();
        $this->handlePrivateEventRequest($addPrivateEventRequest, $requestData);

        $user = $identityHelper->getUser();
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
            $responseData = $this->createGroupEventFromPublicEvent($addPrivateEventRequest->publicEventId, $userGroup);
        } else {
            $responseData = $this->createGroupEventFromScratch($addPrivateEventRequest, $user, $userGroup);
        }
        if($this->eventDispatcher !== null && $responseData['event'] !== null) {
            $addGroupEventEvent = new AddGroupEventEvent($responseData['event']);
            $this->eventDispatcher->dispatch($addGroupEventEvent, 'app.group_event.add');
        }
        return $responseData['response'];
    }

    /**
     * @throws SerializerExceptionInterface
     */
    private function createGroupEventFromPublicEvent(int $publicEventId, UserGroup $userGroup): array
    {
        $notFoundResponse = [
            'event' => null,
            'response' => $this->respondNotFound('Public event not found.')
        ];
        try {
            $publicEvent = $this->eventRepository->findOrFail($publicEventId);
        } catch (EntityNotFoundException) {
            return $notFoundResponse;
        }
        if(!$publicEvent instanceof PublicEvent) {
            return $notFoundResponse;
        }
        $privateEvent = $publicEvent->convertToPrivateEvent($userGroup);
        $this->eventRepository->add($privateEvent);
        $userGroup->addEvent($privateEvent);
        return [
            'event' => $privateEvent,
            'response' => new EventResponse($privateEvent, $this->normalizerFactory)
        ];
    }

    /**
     * @throws SerializerExceptionInterface
     */
    private function createGroupEventFromScratch(
        PrivateEventRequest $addPrivateEventRequest,
        User $eventAuthor,
        UserGroup $userGroup
    ): array
    {
        try {
            $location = $this->placeRepository->findOrFail($addPrivateEventRequest->locationId);
        } catch (EntityNotFoundException) {
            return [
                'event' => null,
                'response' => $this->respondNotFound('Location not found.')
            ];
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
        return [
            'event' => $privateEvent,
            'response' => new EventResponse($privateEvent, $this->normalizerFactory)
        ];
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
    public function enableGroupEventNotifications(
        Request $request,
        int $group_id,
        int $event_id,
        JWTIdentityHelper $identityHelper
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $enableNotification = $requestData["enable24hNotification"];

        $user = $identityHelper->getUser();
        try {
            $userGroup = $this->userGroupRepository->findOrFail($group_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound('User group not found.');
        }

        if(!$userGroup->containsUser($user)) {
            return $this->respondUnauthorized('Unauthorized. You are not a member of this group.');
        }

        try {
             $privateEvent = $this->eventRepository->findOrFail($event_id);
        } catch(EntityNotFoundException) {
            return $this->respondNotFound('Group event not found.');
        }

        if(!$privateEvent instanceof PrivateEvent || !$privateEvent->getUserGroup()->isEqualTo($userGroup)) {
            return $this->respondNotFound('Group event not found.');
        }

        $privateEvent->setNotificationsEnabled($enableNotification);
        $this->eventRepository->update($privateEvent);

        return new EventResponse($privateEvent, $this->normalizerFactory);
    }

    public function createGroup(
        Request $request,
        JWTIdentityHelper $identityHelper
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $userGroupRequest = new CreateUserGroupRequest();
        $this->handleCreateGroupRequest($userGroupRequest, $requestData);
        $user = $identityHelper->getUser();

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

    public function getGroupsForUser(int $user_id, JWTIdentityHelper $identityHelper): JsonResponse
    {
        $user = $identityHelper->getUser();
        if($user->getId() !== $user_id) {
            return $this->respondUnauthorized();
        }
        try {
            $userGroups = $this->userGroupRepository->findAllGroupsForUser($user_id);
            return new GroupsResponse($userGroups, $user, $this->normalizerFactory);
        } catch (\Throwable $e) {
            return $this->respondInternalServerError($e);
        }
    }

    public function getGroups(JWTIdentityHelper $identityHelper): JsonResponse
    {
        $user = $identityHelper->getUser();
        $userGroups = $this->userGroupRepository->findAll();
        try {
            return new GroupsResponse($userGroups, $user, $this->normalizerFactory);
        } catch (SerializerExceptionInterface $e) {
            return $this->respondInternalServerError($e);
        }
    }

    public function getGroupUsers(int $group_id, JWTIdentityHelper $identityHelper): JsonResponse
    {
        $user = $identityHelper->getUser();
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

    public function addGroupUser(Request $request, int $group_id, JWTIdentityHelper $identityHelper): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $userId = $requestData["userId"];

        $currentUser = $identityHelper->getUser();
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

        try {
            return $this->response([
                ...$this->normalizerFactory->getNormalizer($user)->normalize($user, 'json', [
                    'modelProperties' => UserNormalizer::AUTHOR_PROPERTIES
                ]),
                "isAdmin" => false
            ]);
        } catch (SerializerExceptionInterface $e) {
            return $this->respondInternalServerError($e);
        }
    }

    public function leaveGroup(int $group_id, JWTIdentityHelper $identityHelper):JsonResponse
    {
        $user = $identityHelper->getUser();
        try {
            $userGroup = $this->userGroupRepository->findOrFail($group_id);
        } catch (EntityNotFoundException) {
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

    public function deleteGroup(int $group_id, JWTIdentityHelper $identityHelper): JsonResponse
    {
        $user = $identityHelper->getUser();
        try {
            $userGroup = $this->userGroupRepository->findOrFail($group_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }
        if(!$user->isEqualTo($userGroup->getOwner())) {
            return $this->respondUnauthorized();
        }
        try {
            $this->userGroupRepository->delete($userGroup);
        } catch (\Throwable $e) {
            return $this->respondInternalServerError($e);
        }
        return $this->setStatusCode(204)->response([]);
    }

    public function leaveGroupEvent(int $group_id, int $event_id, JWTIdentityHelper $identityHelper): JsonResponse
    {
        $currentUser = $identityHelper->getUser();
        try {
            $userGroup = $this->userGroupRepository->findOrFail($group_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound('User group not found.');
        }

        $isUserInGroup = $userGroup->containsUser($currentUser);
        if(!$isUserInGroup) {
            return $this->respondUnauthorized('Unauthorized. You are not a member of this group.');
        }

        try {
            $event = $this->eventRepository->findOrFail($event_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound('Group event not found.');
        }

        if(!$event instanceof PrivateEvent || !$event->getUserGroup()->isEqualTo($userGroup)) {
            return $this->respondNotFound('Group event not found.');
        }

        $isOwner = $event->getAuthor()->isEqualTo($currentUser);
        if(!$isOwner) {
            return $this->respondUnauthorized('Unauthorized. You are not authorized to delete events in this group.');
        }

        $this->eventRepository->delete($event);

        return $this->setStatusCode(204)->response([]);
    }


}
