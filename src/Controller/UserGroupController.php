<?php

namespace App\Controller;

use App\Exception\FormException;
use App\Form\PrivateEventType;
use App\Factory\NormalizerFactory;
use App\Form\UserGroupDataType;
use App\Model\PrivateEvent;
use App\Model\UserGroup;
use App\Repository\PlaceRepositoryInterface;
use App\Repository\PrivateEventRepositoryInterface;
use App\Repository\UniqueConstraintViolationException;
use App\Repository\UserRepositoryInterface;
use App\Repository\EntityNotFoundException;
use App\Repository\UserGroupRepositoryInterface;
use App\Request\CreateUserGroupRequest;
use App\Request\PrivateEventRequest;
use App\Response\PrivateEventResponse;
use App\Response\GroupUsersResponse;
use App\Response\GroupsResponse;
use App\Serializer\PrivateEventNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class UserGroupController extends ApiController
{
    public function getGroupPrivateEvents(
        int $group_id,
        UserGroupRepositoryInterface $userGroupRepository,
        UserRepositoryInterface $userRepository,
        PrivateEventRepositoryInterface $privateEventRepository
    ): JsonResponse
    {
        $jwtUser = $this->getUser();
        try {
            $user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $userGroup = $userGroupRepository->findOrFail($group_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }

        $isUserInGroup = $userGroup->containsUser($user);
        if(!$isUserInGroup) {
            return $this->respondUnauthorized('Unauthorized. You are not a member of this group.');
        }

        try {
            $privateEvents = $privateEventRepository->findAll($group_id);
        } catch(EntityNotFoundException) {
            return $this->respondNotFound();
        }

        // TODO: move it to Response
        $privateEventsData = [];
        foreach($privateEvents as $privateEvent) {
            $privateEventNormalizer = new PrivateEventNormalizer();
            $privateEventData = $privateEventNormalizer->normalize($privateEvent);
            $eventAuthor = $privateEvent->getAuthor();
            $authorData = [
                'firstName' => $eventAuthor->getFirstName(),
                'lastName' => $eventAuthor->getLastName(),
                'email' => $eventAuthor->getUserIdentifier()
            ];
            $privateEventsData [] = [
                ...$privateEventData,
                "author" => $authorData
            ];
        }

        return $this->response(["events" => $privateEventsData]);
    }

    public function createGroupPrivateEvent(
        int $group_id,
        Request $request,
        UserRepositoryInterface $userRepository,
        UserGroupRepositoryInterface $userGroupRepository,
        PrivateEventRepositoryInterface $privateEventRepository,
        PlaceRepositoryInterface $placeRepository
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $addPrivateEventRequest = new PrivateEventRequest();

        $this->handlePrivateEventRequest($addPrivateEventRequest, $requestData);

        //TODO: private event based on public event

        $jwtUser = $this->getUser();
        try {
            $user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $location = $placeRepository->findOrFail((int)$requestData['locationId']);
            $userGroup = $userGroupRepository->findOrFail($group_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }

        $isUserInGroup = $userGroup->containsUser($user);
        if(!$isUserInGroup) {
            return $this->respondUnauthorized('Unauthorized. You are not a member of this group.');
        }

        $privateEvent = new PrivateEvent(
            null,
            $addPrivateEventRequest->name,
            $location,
            $addPrivateEventRequest->description,
            $addPrivateEventRequest->startDate,
            $user,
            $userGroup
        );

        $privateEventRepository->add($privateEvent);
        $userGroup->addEvent($privateEvent);

        return new PrivateEventResponse($privateEvent);
    }

    private function handlePrivateEventRequest(PrivateEventRequest $request, mixed $requestData): void
    {
        $form = $this->createForm(PrivateEventType::class, $request);
        $form->submit($requestData);
        if (!$form->isValid()) {
            throw new FormException($form);
        }

    }

    //TODO: enableGroupEventNotifications
    public function enableGroupEventNotifications(
        Request $request,
        int $group_id,
        int $event_id,
        UserGroupRepositoryInterface $userGroupRepository,
        UserRepositoryInterface $userRepository,
        PrivateEventRepositoryInterface $privateEventRepository
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $enableNotification = $requestData["enable24hNotification"];

        $jwtUser = $this->getUser();
        try {
            $user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $userGroup = $userGroupRepository->findOrFail($group_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }

        $isUserInGroup = $userGroup->containsUser($user);
        if(!$isUserInGroup) {
            return $this->respondUnauthorized('Unauthorized. You are not a member of this group.');
        }

        try {
            $privateEvent = $privateEventRepository->findOrFail($event_id);
            $groupEvents = $privateEventRepository->findAll($group_id);
        } catch(EntityNotFoundException) {
            return $this->respondNotFound();
        }

        // check if event is in group
        $eventInGroup = false;
        foreach($groupEvents as $groupEvent) {
            if($privateEvent->isEqualTo($groupEvent)) {
                $eventInGroup = true;
                break;
            }
        }

        if(!$eventInGroup) {
            return $this->respondNotFound();
        }

        $privateEvent->setNotificationsEnabled($enableNotification);
        $privateEventRepository->update($privateEvent);



        return new PrivateEventResponse($privateEvent);
    }

    public function createGroup(
        Request $request,
        UserGroupRepositoryInterface $userGroupRepository,
        UserRepositoryInterface $userRepository
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $userGroupRequest = new CreateUserGroupRequest();
        $this->handleCreateGroupRequest($userGroupRequest, $requestData);
        $jwtUser = $this->getUser();
        try {
            $user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }

        $userGroup = new UserGroup(null, $userGroupRequest->name, $user);
        $userGroup->addUser($user);

        try {
            $userGroupRepository->add($userGroup);
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

    public function getGroups(
        UserGroupRepositoryInterface $userGroupRepository,
        UserRepositoryInterface $userRepository,
        NormalizerFactory $normalizerFactory
    ): JsonResponse
    {
        $jwtUser = $this->getUser();
        try {
            $user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
            $userGroups = $userGroupRepository->findAll();
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            return new GroupsResponse($userGroups, $user, $normalizerFactory);
        } catch (ExceptionInterface $e) {
            return $this->respondInternalServerError($e);
        }
    }

    public function getGroupUsers(
        int $group_id,
        UserGroupRepositoryInterface $userGroupRepository,
        UserRepositoryInterface $userRepository,
        NormalizerFactory $normalizerFactory
    ): JsonResponse
    {
        $jwtUser = $this->getUser();
        try {
            $user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $userGroup = $userGroupRepository->findOrFail($group_id);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound();
        }

        $isUserInGroup = $userGroup->containsUser($user);
        if(!$isUserInGroup) {
            return $this->respondUnauthorized('Unauthorized. You are not a member of this group.');
        }
        try {
            return new GroupUsersResponse($userGroup, $user, $normalizerFactory);
        } catch (ExceptionInterface $e) {
            return $this->respondInternalServerError($e);
        }
    }

    public function addGroupUser(
        Request $request,
        int $group_id,
        UserGroupRepositoryInterface $userGroupRepository,
        UserRepositoryInterface $userRepository
    ): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);
        $userId = $requestData["userId"];

        $jwtUser = $this->getUser();
        try {
            $currentUser = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $user = $userRepository->findByIdOrFail($userId);
        } catch (EntityNotFoundException) {
            return $this->respondNotFound('User with given id does not exist.');
        }
        try {
            $userGroup = $userGroupRepository->findOrFail($group_id);
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
            $userGroupRepository->addGroupUser($userGroup, $user);
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
            "id" => $user->getId(),
            "firstName" => $user->getFirstName(),
            "lastName" => $user->getLastName(),
            "email" => $user->getEmail(),
            "isAdmin" => false
        ]);
    }

    public function leaveGroup(
        Request $request,
        int $group_id,
        UserGroupRepositoryInterface $userGroupRepository,
        UserRepositoryInterface $userRepository,
        NormalizerFactory $normalizerFactory
    ):JsonResponse
    {
        $jwtUser = $this->getUser();
        try {
            $user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }
        try {
            $userGroup = $userGroupRepository->findOrFail($group_id);
        } catch (EntityNotFoundException $e) {
            return $this->respondNotFound();
        }

        // when owner leaves the group, a group is deleted (users are deleted on cascade)
        if($user->isEqualTo($userGroup->getOwner())) {
            $userGroupRepository->delete($userGroup);
        } else {
            $userGroupRepository->deleteUserFromGroup($userGroup->getGroupId(), $user->getId());
        }

        $userGroups = $userGroupRepository->findAllGroupsForUser($user->getId());

        try {
            return new GroupsResponse($userGroups, $user, $normalizerFactory);
        } catch (ExceptionInterface $e) {
            return $this->respondInternalServerError($e);
        }
    }

    public function leaveGroupEvent(int $event_id): JsonResponse
    {
        return $this->setStatusCode(204)->response([]);
    }
}
