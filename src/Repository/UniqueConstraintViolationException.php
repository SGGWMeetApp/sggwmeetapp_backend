<?php

namespace App\Repository;

class UniqueConstraintViolationException extends \Exception
{
    private string $violatedConstraint;

    public function __construct(string $violatedConstraint)
    {
        $this->violatedConstraint = $violatedConstraint;
        parent::__construct('Unique constraint violation occurred for constraint ' . $violatedConstraint);
    }

    /**
     * @return string
     */
    public function getViolatedConstraint(): string
    {
        return $this->violatedConstraint;
    }

}