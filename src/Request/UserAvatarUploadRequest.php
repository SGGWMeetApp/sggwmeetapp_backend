<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UserAvatarUploadRequest
{
    /**
     * @Assert\NotBlank()
     */
    private $avatar;

    private $decodedData;

    public function setAvatar(?string $avatar): void
    {
        $this->avatar = $avatar;
        $this->decodedData = base64_decode($avatar);
    }

    /**
     * @return mixed
     */
    public function getDecodedData(): mixed
    {
        return $this->decodedData;
    }
}