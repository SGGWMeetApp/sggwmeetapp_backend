<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

class JWTAuthFailureListener
{
    public function onJWTAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $data = [
            'errorCode' => 'INVALID_CREDENTIALS',
            'message' => 'Invalid credentials.'
        ];
        $response = new JsonResponse($data, 401);
        $response->setData($data);

        $event->setResponse($response);
    }
}