<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GroupController extends ApiController
{
    public function CreateGroup(Request $request): JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);


        return $this->response([
            "id"=> "1", //String
            "name"=> $requestData['name'] //String
        ]);
    }

    public function getGroups(Request $request): JsonResponse
    {
        return $this->response(['groups' => [
            [
                "id" => 1,
                "name" => "WielkieChlopy",
                "memberCount" => 5, 
                "adminData"=> [
                    "firstName"=> "Paweł", //String
                    "lastName"=> "Górczewski", //String
                    "isUserAdmin"=> true //bool
                ],
                "incomingEventsCount"=> 5 //int

            ],
            [
                "id" => 2,
                "name" => "InformatykaSem1Rok1",
                "memberCount" => 75, 
                "adminData"=> [
                    "firstName"=> "Jan", //String
                    "lastName"=> "Kowalski", //String
                    "isUserAdmin"=> true //bool
                ],
                "incomingEventsCount"=> 0 //int
 
            ],
        ]]);
    }

    public function getGroupUsers(int $group_id): JsonResponse
    {
        return $this->response([
            "id" => $group_id,
            "name" => "WielkieChlopy",
            "isUserAdmin" => true, //to bym wyjebał
            "users" => [
                [
                    "id"=> "1", //String  jaki string ma być?
                    "firstName" => "Paweł", //String
                    "lastName"=> "Górczewski", //String
                    "email" => "WielkiCHłop@mail.pl", //String
                    "isAdmin" => true //bool
                ],
                [
                    "id"=> "2", //String  jaki string ma być?
                    "firstName" => "Jan", //String
                    "lastName"=> "Kowalski", //String
                    "email" => "WielkiCHłop2@mail.pl", //String
                    "isAdmin" => false //bool
                ],
            ]
        ]);
    }

    public function addGroupUser(Request $request, int $group_id):JsonResponse
    {
        $requestData = json_decode($request->getContent(),true);

        //Pobranie danych zbazy po userID 

        return $this->response([
            "id"=> $requestData["userId"], //String
            "firstName"=> "Maciek", //String
            "lastName"=> "Kucharski", //String
            "email"=> "mkmkmk@mieuł.qw", //String
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
                    "firstName"=> "Paweł", //String
                    "lastName"=> "Górczewski", //String
                    "isUserAdmin"=> true //bool
                ],
                "incomingEventsCount"=> 5 //int

            ],
            [
                "id" => 2,
                "name" => "InformatykaSem1Rok1",
                "memberCount" => 75, 
                "adminData"=> [
                    "firstName"=> "Jan", //String
                    "lastName"=> "Kowalski", //String
                    "isUserAdmin"=> true //bool
                ],
                "incomingEventsCount"=> 0 //int
 
            ],
        ]]);
    }


}
