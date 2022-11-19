<?php

namespace App\Controller;

use App\Exception\FormException;
use App\Form\UserGroupDataType;
use App\Model\UserGroup;
use App\Repository\UniqueConstraintViolationException;
use App\Repository\UserRepositoryInterface;
use App\Repository\EntityNotFoundException;
use App\Repository\UserGroupRepositoryInterface;
use App\Request\CreateUserGroupRequest;
use App\Response\GroupUsersResponse;
use App\Response\GroupsResponse;
use App\Serializer\UserGroupNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Exception\ExceptionInterface;


class UserGroupController extends ApiController
{

    // TODO: getGroupPrivateEventsAction
    public function getGroupPrivateEventsAction(int $group_id): JsonResponse
    {
        return $this->response(["events" => [
            [
                "id" => 1,
                "name" => "Urodziny Marleny",
                "description" => "W sobotę 5 listopada imprezka w klubie Niebo z okazji moich urodzin! Wpadajcie o 19 na bifor na miasteczko SGGW!",
                "startDate" => "2022-11-05T20:00:00.000Z",
                "locationData" => [
                    "name" => "Klub Niebo"
                ],
                "author" => [
                    "firstName" => "Marlena",
                    "lastName" => "Kowalska",
                    "email" => "mkowalska123@email.com"
                ],
                "canEdit" => false,
                "notification24hEnabled" => true
            ]
        ]]);
    }

    //TODO: createGroupPrivateEventAction
    public function createGroupPrivateEventAction(Request $request): JsonResponse
    {
        // get data from request

        // add event to database

        // return event
        return $this->response([
            "id" => 1,
            "name" => "Urodziny Marleny",
            "description" => "W sobotę 5 listopada imprezka w klubie Niebo z okazji moich urodzin! Wpadajcie o 19 na bifor na miasteczko SGGW!",
            "startDate" => "2022-11-05T20:00:00.000Z",
            "locationData" => [
                "name" => "Klub Niebo"
            ],
            "author" => [
                "firstName" => "Marlena",
                "lastName" => "Kowalska",
                "email" => "mkowalska123@email.com"
            ],
            "canEdit" => true,
            "notification24hEnabled" => false
        ]);
    }

    //TODO: enableGroupEventNotifications
    public function enableGroupEventNotifications(Request $request, int $group_id, int $event_id): JsonResponse
    {
        return $this->response([
            "id" => 1,
            "name" => "Urodziny Marleny",
            "description" => "W sobotę 5 listopada imprezka w klubie Niebo z okazji moich urodzin! Wpadajcie o 19 na bifor na miasteczko SGGW!",
            "startDate" => "2022-11-05T20:00:00.000Z",
            "locationData" => [
                "name" => "Klub Niebo"
            ],
            "author" => [
                "firstName" => "Marlena",
                "lastName" => "Kowalska",
                "email" => "mkowalska123@email.com"
            ],
            "canEdit" => true,
            "notification24hEnabled" => true
        ]);
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
        UserRepositoryInterface $userRepository
    ): JsonResponse
    {
        $jwtUser = $this->getUser();
        try {
            $user = $userRepository->findOrFail($jwtUser->getUserIdentifier());
            $userGroups = $userGroupRepository->findAll();
        } catch (EntityNotFoundException $e) {
            return $this->respondInternalServerError($e);
        }

        return new GroupsResponse($userGroups, $user);
    }

    public function getGroupUsers(
        int $group_id,
        UserGroupRepositoryInterface $userGroupRepository,
        UserRepositoryInterface $userRepository
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
        $groupUsers = $userGroup->getUsers();
        $hasListAccess = false;
        foreach ($groupUsers as $groupUser) {
            if ($groupUser->isEqualTo($user)) {
                $hasListAccess = true;
                break;
            }
        }
        if(!$hasListAccess) {
            return $this->respondUnauthorized('Unauthorized. You are not a member of this group.');
        }

        return new GroupUsersResponse($userGroup, $user);
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

        $groupUsers = $userGroup->getUsers();
        $hasListAccess = false;
        foreach ($groupUsers as $groupUser) {
            if ($groupUser->isEqualTo($currentUser)) {
                $hasListAccess = true;
                break;
            }
        }
        if(!$hasListAccess) {
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
        UserRepositoryInterface $userRepository
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

        return new GroupsResponse($userGroups, $user);
    }

    public function leaveGroupEvent(int $event_id): JsonResponse
    {
        return $this->setStatusCode(204)->response([]);
    }
}
