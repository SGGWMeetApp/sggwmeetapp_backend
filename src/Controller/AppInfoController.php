<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;

class AppInfoController extends ApiController
{
    public function appInfo(): Response
    {
        return $this->response([
            'version' => '0.4',
            'name' => 'SGGW MeetApp REST API',
            'status' => 'WIP'
        ]);
    }
}