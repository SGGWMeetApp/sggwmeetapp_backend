<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UserAvatarUploadRequest
{
    /**
     * @Assert\NotBlank()
     */
    public $base64file;

    private $decodedData;

    public function setBase64file(?string $base64file): void
    {
        $this->base64file = $base64file;
        $this->decodedData = base64_decode($base64file);
    }

    /**
     * @return mixed
     */
    public function getDecodedData(): mixed
    {
        return $this->decodedData;
    }
}