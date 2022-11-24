<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserRequest
{
    /**
     * @Assert\Collection(
     *     fields={
     *          "firstName" = {
     *              @Assert\NotBlank(allowNull=true),
     *              @Assert\Type("string"),
     *              @Assert\Length(min=1, max=255)
     *          },
     *          "lastName" = {
     *              @Assert\NotBlank(allowNull=true),
     *              @Assert\Type("string"),
     *              @Assert\Length(min=1, max=255)
     *          },
     *          "phoneNumberPrefix" = {
     *              @Assert\NotBlank(allowNull=true),
     *              @Assert\Type("string"),
     *              @Assert\Regex(pattern="/\d+/"),
     *              @Assert\Length(min=1, max=4)
     *          },
     *          "phoneNumber" = {
     *              @Assert\NotBlank(allowNull=true),
     *              @Assert\Type("string"),
     *              @Assert\Regex(pattern="/\d+/"),
     *              @Assert\Length(min=1, max=15)
     *          },
     *          "description" = {
     *              @Assert\NotBlank(allowNull=true),
     *              @Assert\Type("string")
     *          },
     *     }
     * )
     */
    public $userData;
}