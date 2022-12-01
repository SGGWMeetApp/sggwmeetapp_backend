<?php

namespace App\Repository;

use App\Model\Event;
use App\Model\Place;
use App\Model\UserGroup;

interface EventRepositoryInterface
{
    /**
     * @throws EntityNotFoundException
     */
    public function findOrFail(int $eventId): Event;

    public function findAllForGroup(UserGroup $userGroup): array;

    public function findAll(): array;

    public function findUpcoming(): array;

    public function findAllForPlace(Place $place): array;

    public function add(Event $event): void;

    public function update(Event $event): void;

    public function delete(Event $event): void;

}