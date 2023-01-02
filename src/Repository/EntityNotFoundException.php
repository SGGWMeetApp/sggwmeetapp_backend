<?php

namespace App\Repository;

class EntityNotFoundException extends \Exception
{

    public function __construct()
    {
        parent::__construct('Requested entity was not found in the database.');
    }
}