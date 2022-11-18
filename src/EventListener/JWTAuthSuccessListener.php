<?php

namespace App\EventListener;

use App\Security\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class JWTAuthSuccessListener
{
    public function onJWTAuthenticationSuccess(AuthenticationSuccessEvent $event)
    {
        $data = $event->getData();
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }
        $data['userData'] = [
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'phoneNumberPrefix' => $user->getPhonePrefix(),
            'phoneNumber' => $user->getPhone(),
            'description' => $user->getDescription(),
            'avatarUrl' => ''
        ];
        $event->setData($data);
    }
}