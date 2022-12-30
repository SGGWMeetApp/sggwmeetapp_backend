<?php

namespace App\Repository;

use App\Model\User;

interface EventAttendersRepositoryInterface
{
    public function findUpcommingEventsAttenders(): array;
}