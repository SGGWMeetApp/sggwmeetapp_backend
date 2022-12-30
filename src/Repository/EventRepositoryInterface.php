<?php

namespace App\Repository;

use App\Model\Event;
use App\Model\Place;
use App\Model\UserGroup;
use App\Security\User;

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

    /**
     * @throws UniqueConstraintViolationException
     */
    public function addUserToEventAttenders(User $user, Event $event): void;

    public function removeUserFromEventAttenders(User $user, Event $event): void;

    public function getAttenders(Event $event): array;

    public function findAllForUser(User $user): array;

    /**
     * Returns pairs of (int event_id, bool attends). Array is indexed using event ids.
     * @param User $user
     * @param Event ...$events
     * @return array
     */
    public function checkUserAttendance(User $user, Event ...$events): array;

    /**
     * Returns pairs of (Event $event, [User $user]).
     * @param int $upcommingTimeInMinutes
     * @param int $notificationIntervalInMinutes
     * @return array
     */
    public function findUpcommingEventAttenders(int $upcommingTimeInMinutes, int $notificationIntervalInMinutes): array;

}