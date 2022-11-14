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
use App\Response\UserGroupResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class UserGroupController extends ApiController
{

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
        try {
            $groupId = $userGroupRepository->add($userGroup);
            $userGroup->setGroupId($groupId);
        } catch(\Exception $e) {

        }

        return new UserGroupResponse($userGroup);
    }

    private function handleCreateGroupRequest(CreateUserGroupRequest $request, mixed $requestData): void
    {
        $form = $this->createForm(UserGroupDataType::class, $request);
        $form->submit($requestData);
        if (!$form->isValid()) {
            throw new FormException($form);
        }
    }


    public function getGroups(Request $request): JsonResponse
    {
        return $this->response(['groups' => [
            [
                "id" => 1,
                "name" => "WielkieChlopy",
                "memberCount" => 5, 
                "adminData"=> [
                    "firstName"=> "Paweł",
                    "lastName"=> "Górczewski",
                    "isUserAdmin"=> true
                ],
                "incomingEventsCount"=> 5

            ],
            [
                "id" => 2,
                "name" => "InformatykaSem1Rok1",
                "memberCount" => 75, 
                "adminData"=> [
                    "firstName"=> "Jan",
                    "lastName"=> "Kowalski",
                    "isUserAdmin"=> true
                ],
                "incomingEventsCount"=> 0
            ],
        ]]);
    }

    public function getGroupUsers(int $group_id): JsonResponse
    {
        return $this->response([
            "id" => $group_id,
            "name" => "WielkieChlopy",
            "isUserAdmin" => true,
            "users" => [
                [
                    "id"=> 1,
                    "firstName" => "Paweł",
                    "lastName"=> "Górczewski",
                    "email" => "WielkiCHłop@mail.pl",
                    "isAdmin" => true
                ],
                [
                    "id"=> 2,
                    "firstName" => "Jan",
                    "lastName"=> "Kowalski",
                    "email" => "WielkiCHłop2@mail.pl",
                    "isAdmin" => false
                ],
            ]
        ]);
    }

    public function addGroupUser(Request $request, int $group_id):JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);

        //Pobranie danych zbazy po userID 

        return $this->response([
            "id"=> $requestData["userId"],
            "firstName"=> "Maciek",
            "lastName"=> "Kucharski",
            "email"=> "mkmkmk@mieuł.qw",
            "isAdmin"=> false, //bool
            "JoinDate" => date("Y-m-d")
        ]);
    }

    public function leaveGroup(Request $request, int $group_id):JsonResponse
    {
        return $this->response(['groups' => [
            [
                "id" => 1,
                "name" => "WielkieChlopy",
                "memberCount" => 5, 
                "adminData"=> [
                    "firstName"=> "Paweł",
                    "lastName"=> "Górczewski",
                    "isUserAdmin"=> true
                ],
                "incomingEventsCount"=> 5
            ],
            [
                "id" => 2,
                "name" => "InformatykaSem1Rok1",
                "memberCount" => 75, 
                "adminData"=> [
                    "firstName"=> "Jan",
                    "lastName"=> "Kowalski",
                    "isUserAdmin"=> true
                ],
                "incomingEventsCount"=> 0
            ],
        ]]);
    }

    public function leaveGroupEvent(int $event_id): JsonResponse
    {
        return $this->setStatusCode(204)->response([]);
    }
}
