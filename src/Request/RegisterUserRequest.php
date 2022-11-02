<?php

namespace App\Request;

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
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Assert\Length(min="8")
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
     *          "phoneNumber" = {
     *              @Assert\NotBlank(),
     *              @Assert\Type("string"),
     *              @Assert\Length(min=1, max=255)
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