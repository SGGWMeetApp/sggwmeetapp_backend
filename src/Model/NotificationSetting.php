<?php

namespace App\Model;

class NotificationSetting
{
    private string $name;
    private bool $enabled;

    /**
     * @param string $name
     * @param bool $enabled
     */
    public function __construct(string $name, bool $enabled)
    {
        $this->name = $name;
        $this->enabled = $enabled;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function enable(): void {
        $this->enabled = true;
    }

    public function disable(): void {
        $this->enabled = false;
    }
}