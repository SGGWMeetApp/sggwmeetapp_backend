<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends AbstractController
{

    public function appInfo(): Response
    {
        return new JsonResponse([
            'version' => '0.1',
            'name' => 'SGGW MeetApp REST API',
            'status' => 'WIP'
        ], 200);
    }
}