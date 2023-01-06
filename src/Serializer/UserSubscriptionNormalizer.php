<?php

namespace App\Serializer;

use App\Model\UserSubscription;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserSubscriptionNormalizer implements NormalizerInterface, DenormalizerInterface
{

    /**
     * @inheritDoc
     */
    public function normalize($object, string $format = null, array $context = []): float|int|bool|\ArrayObject|array|string|null
    {
        if (!$object instanceof UserSubscription) {
            throw new InvalidArgumentException("This normalizer only support instances of ".UserSubscription::class);
        }
        return [
            'id' => $object->getId(),
            'subscriptionHash' => $object->getSubscriptionHash(),
            'subscription' => $object->getSubscription()
        ];
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof UserSubscription;
    }

    public function denormalize($data, string $type, string $format = null, array $context = []): UserSubscription
    {
        $subscriptionData = json_decode($data['subscription'], true);
        $subscriptionObject = new UserSubscription(
            $data['user'],
            $data['subscription_hash'],
            $subscriptionData
        );
        $subscriptionObject->setId($data['subscription_id']);
        return $subscriptionObject;
    }

    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return $type === UserSubscription::class;
    }
}