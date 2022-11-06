<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class GroupController extends ApiController {

    public function getGroupPrivateEventsAction(int $group_id): JsonResponse {
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
                "canEdit" => false
            ]
        ]]);
    }

    public function createPrivateEventAction(Request $request): JsonResponse {
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
            "canEdit" => true
        ]);
    }
}