<?php

namespace App\Request;

use App\Constraint\UserPassword;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterUserRequest
{
    /**
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Assert\Email()
     * @var string
     */
    public $email;

    /**
     * @UserPassword()
     * @var string
     */
    public $password;

    /**
     * @Assert\Collection(
     *     fields={
     *          "firstName" = {
     *              @Assert\NotBlank(),
     *              @Assert\Type("string"),
     *              @Assert\Length(min=1, max=255)
     *          },
     *          "lastName" = {
     *              @Assert\NotBlank(),
     *              @Assert\Type("string"),
     *              @Assert\Length(min=1, max=255)
     *          },
     *          "phoneNumberPrefix" = {
     *              @Assert\NotBlank(),
     *              @Assert\Type("string"),
     *              @Assert\Regex(pattern="/\d+/"),
     *              @Assert\Length(min=1, max=4)
     *          },
     *          "phoneNumber" = {
     *              @Assert\NotBlank(),
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