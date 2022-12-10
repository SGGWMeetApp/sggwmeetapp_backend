<?php

namespace App\Model;

abstract class Notification
{
    private ?string $title;
    private ?string $message;
    private bool $enabled;

    /**
     * @param string|null $title
     * @param string|null $message
     * @param bool $enabled
     */
    public function __construct(?string $title = null, ?string $message = null, bool $enabled = true)
    {
        $this->title = $title;
        $this->message = $message;
        $this->enabled = $enabled;
    }


    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }
}