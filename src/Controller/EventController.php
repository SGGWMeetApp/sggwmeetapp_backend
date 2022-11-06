<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class EventController extends ApiController {

    public function getPublicEventsAction(Request $request): JsonResponse {

        // 1. Get filters from request (converted to filters object)

        // 2. Get filtered events from database
        return $this->response(["events" => [
            [
                "id" => 1,
                "name" => "Planszówki",
                "description" => "Zapraszamy na świąteczną edycję planszówek! Wybierz jedną z setek gier i baw się razem z nami!",
                "startDate" => "2022-12-23T18:30:00.000Z",
                "locationData" => [
                    "name" => "Dziekanat 161"
                ],
                "author" => [
                    "firstName" => "Joanna",
                    "lastName" => "Nowak",
                    "email" => "joanna.nowak@email.com"
                ],
                "canEdit" => true
            ],
            [
                "id" => 2,
                "name" => "Środowe Disco",
                "description" => "Już w tą środę widzimy się na parkiecie w Dziekanacie! Dobra zabawa gwarantowana! Do 22:00 bilet 10 zł, Po 22:00 15 zł.",
                "startDate" => "2022-11-06T21:00:00.000Z",
                "locationData" => [
                    "name" => "Dziekanat 161"
                ],
                "author" => [
                    "firstName" => "Jerzy",
                    "lastName" => "Dudek",
                    "email" => "jerzy.dudek@example.com"
                ],
                "canEdit" => false
            ],
        ]]);
    }

    public function createPublicEventAction(Request $request): JsonResponse {
        // get data from request

        // add event to database

        // return event
        return $this->response([
            "id" => 1,
            "name" => "Planszówki",
            "description" => "Zapraszamy na świąteczną edycję planszówek! Wybierz jedną z setek gier i baw się razem z nami!",
            "startDate" => "2022-12-23T18:30:00.000Z",
            "locationData" => [
                "name" => "Dziekanat 161"
            ],
            "author" => [
                "firstName" => "Joanna",
                "lastName" => "Nowak",
                "email" => "joanna.nowak@email.com"
            ],
            "canEdit" => true
        ]);
    }

    public function updateEventAction(Request $request): JsonResponse {
        // check if can edit (only author)

        // edit event

        // return edited event
        return $this->response([
            "id" => 2,
            "name" => "Środowe Disco",
            "description" => "Już w tą środę widzimy się na parkiecie w Dziekanacie! Dobra zabawa gwarantowana! Do 22:00 bilet 15 zł, Po 22:00 20 zł.",
            "startDate" => "2022-11-06T21:00:00.000Z",
            "locationData" => [
                "name" => "Dziekanat 161"
            ],
            "author" => [
                "firstName" => "Jerzy",
                "lastName" => "Dudek",
                "email" => "jerzy.dudek@example.com"
            ],
            "canEdit" => true
        ]);
    }

    public function getUpcomingEventsAction(): JsonResponse {
        // get events where startDate < sysdate + 1 week

        // return events
        return $this->response(["events" => [
            [
                "id" => 2,
                "name" => "Środowe Disco",
                "description" => "Już w tą środę widzimy się na parkiecie w Dziekanacie! Dobra zabawa gwarantowana! Do 22:00 bilet 10 zł, Po 22:00 15 zł.",
                "startDate" => "2022-11-06T21:00:00.000Z",
                "locationData" => [
                    "name" => "Dziekanat 161"
                ],
                "author" => [
                    "firstName" => "Jerzy",
                    "lastName" => "Dudek",
                    "email" => "jerzy.dudek@example.com"
                ],
                "canEdit" => false
            ],
        ]]);
    }

}