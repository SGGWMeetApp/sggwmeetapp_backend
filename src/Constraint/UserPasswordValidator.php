<?php

namespace App\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UserPasswordValidator extends ConstraintValidator
{

    public function validate(mixed $value, Constraint $constraint)
    {
        if (!$constraint instanceof UserPassword) {
            throw new UnexpectedTypeException($constraint, UserPassword::class);
        }
        $context = $this->context;
        $validator = $context->getValidator();
        $validations = $validator->validate($value, $constraint->getConstraints());

        if ($validations->count() > 0) {
            foreach ($validations as $validation) {
                $this->context->buildViolation($validation->getMessage())->addViolation();
            }
        }
    }
}