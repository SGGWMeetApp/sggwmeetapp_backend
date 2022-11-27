<?php

namespace App\EventListener;

use App\Security\User;
use League\Flysystem\Filesystem;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class JWTAuthSuccessListener
{
    private Filesystem $filesystem;

    public function __construct(Filesystem $uploadsFilesystem)
    {
        $this->filesystem = $uploadsFilesystem;
    }

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
            'avatarUrl' => $user->getAvatarUrl() ? $this->filesystem->publicUrl($user->getAvatarUrl()) : null
        ];
        $event->setData($data);
    }
}