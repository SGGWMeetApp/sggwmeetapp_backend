<?php

namespace App\Model;

use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserSubscription implements UserSubscriptionInterface
{
    private ?int $id;
    private UserInterface $user;
    private string $subscriptionHash;
    private array $subscription;

    public function __construct(UserInterface $user, string $subscriptionHash, array $subscription)
    {
        $this->user = $user;
        $this->subscriptionHash = $subscriptionHash;
        $this->subscription = $subscription;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return array
     */
    public function getSubscription(): array
    {
        return $this->subscription;
    }

    /**
     * @param array $subscription
     */
    public function setSubscription(array $subscription): void
    {
        $this->subscription = $subscription;
    }

    /**
     * @inheritDoc
     */
    public function getUser(): UserInterface
    {
        return $this->user;
    }

    /**
     * @inheritDoc
     */
    public function getSubscriptionHash(): string
    {
        return $this->subscriptionHash;
    }

    /**
     * @inheritDoc
     */
    public function getEndpoint(): string
    {
        return $this->subscription['endpoint'];
    }

    /**
     * @inheritDoc
     */
    public function getPublicKey(): string
    {
        return $this->subscription['keys']['p256dh'];
    }

    /**
     * @inheritDoc
     */
    public function getAuthToken(): string
    {
        return $this->subscription['keys']['auth'];
    }

    /**
     * @inheritDoc
     */
    public function getContentEncoding(): string
    {
        return $this->subscription['content-encoding'] ?? 'aesgcm';
    }
}