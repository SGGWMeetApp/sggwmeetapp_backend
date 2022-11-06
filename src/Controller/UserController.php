<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserController extends ApiController
{
    public function researchUsers(Request $request): JsonResponse
    {
        return $this->response(["users" => [
            [
                "id"=> "1", //String
                "firstName"=> "Ala", //String
                "lastName"=> "Nowak", //String
                "email"=> "anowak@mail.pl" //String
            ],
            [
                "id"=> "2", //String
                "firstName"=> "Ola", //String
                "lastName"=> "Kowalska", //String
                "email"=> "okowalska@mail.pl" //String
            ],
        ]
        ]);

    }

}

