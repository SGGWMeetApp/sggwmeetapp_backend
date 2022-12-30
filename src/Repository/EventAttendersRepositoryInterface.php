<?php

namespace App\Repository;

interface EventAttendersRepositoryInterface
{
    public function findUpcommingEventsAttenders(): array;
}