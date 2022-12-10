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

    public function findAllPublicEvents(): array;

    public function findUpcomingPublicEvents(): array;

    public function findAllPublicEventsForPlace(Place $place): array;

    public function add(Event $event): void;

    public function update(Event $event): void;

    public function delete(Event $event): void;

}