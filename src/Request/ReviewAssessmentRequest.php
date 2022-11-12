<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ReviewAssessmentRequest
{
    /**
     * @Assert\Type(type="boolean")
     * @var boolean
     */
    public $isPositive = null;
}