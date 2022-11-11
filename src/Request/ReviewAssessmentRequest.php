<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ReviewAssessmentRequest
{
    /**
     * @Assert\NotNull()
     * @Assert\Type("boolean")
     * @var boolean
     */
    public $isPositive;
}