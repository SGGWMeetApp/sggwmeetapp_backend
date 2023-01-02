<?php

namespace App\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Annotation
 */
class UserPassword extends Constraint
{
    public $message = 'Invalid password. {{ error }}';

    public function getConstraints(): array
    {
        return [
            new Assert\NotBlank(),
            new Assert\Length(['min' => 8, 'max' => 4096])
        ];
    }
}