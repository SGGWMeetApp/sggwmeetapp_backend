<?php

namespace App\Serializer;

use App\Model\NotificationSetting;
use App\Model\UserNotificationSettings;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserNotificationSettingsNormalizer implements NormalizerInterface
{

    /**
     * @inheritDoc
     */
    public function normalize(mixed $object, string $format = null, array $context = []): float|array|bool|\ArrayObject|int|string|null
    {
        if (!$object instanceof UserNotificationSettings) {
            throw new InvalidArgumentException("This normalizer only support instances of ".UserNotificationSettings::class);
        }
        $normalizedSettings = [];
        /** @var NotificationSetting $setting */
        foreach ($object->getSettings() as $setting) {
            $normalizedSettings [$setting->getName()] = $setting->isEnabled();
        }
        return $normalizedSettings;
    }

    /**
     * @inheritDoc
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof UserNotificationSettings;
    }
}