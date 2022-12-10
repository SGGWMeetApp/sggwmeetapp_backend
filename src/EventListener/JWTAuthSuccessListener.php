<?php

namespace App\EventListener;

use App\Factory\NormalizerFactory;
use App\Security\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class JWTAuthSuccessListener
{
    private NormalizerFactory $normalizerFactory;

    public function __construct(NormalizerFactory $normalizerFactory)
    {
        $this->normalizerFactory = $normalizerFactory;
    }

    /**
     * @throws SerializerExceptionInterface
     */
    public function onJWTAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }
        $data['userData'] = $this->normalizerFactory->getNormalizer($user)->normalize($user);
        $event->setData($data);
    }
}