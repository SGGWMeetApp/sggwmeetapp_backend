<?php

namespace App\Model;

use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;

class ResetPasswordRequest implements ResetPasswordRequestInterface
{
    private string $selector;
    private string $hashedToken;
    private \DateTimeInterface $requestedAt;
    private \DateTimeInterface $expiresAt;
    private object $user;

    /**
     * @param object $user
     * @param string $selector
     * @param string $hashedToken
     * @param \DateTimeInterface $requestedAt
     * @param \DateTimeInterface $expiresAt
     */
    public function __construct(object $user, string $selector, string $hashedToken, \DateTimeInterface $requestedAt, \DateTimeInterface $expiresAt)
    {
        $this->user = $user;
        $this->selector = $selector;
        $this->hashedToken = $hashedToken;
        $this->requestedAt = $requestedAt;
        $this->expiresAt = $expiresAt;
    }

    /**
     * @inheritDoc
     */
    public function getRequestedAt(): \DateTimeInterface
    {
        return $this->requestedAt;
    }

    /**
     * @inheritDoc
     */
    public function isExpired(): bool
    {
        return $this->expiresAt->getTimestamp() <= time();
    }

    /**
     * @inheritDoc
     */
    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expiresAt;
    }

    /**
     * @inheritDoc
     */
    public function getHashedToken(): string
    {
        return $this->hashedToken;
    }

    /**
     * @inheritDoc
     */
    public function getUser(): object
    {
        return $this->user;
    }

    public function getSelector(): string
    {
        return $this->selector;
    }

}