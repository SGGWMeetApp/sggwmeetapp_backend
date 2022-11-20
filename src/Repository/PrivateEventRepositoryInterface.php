<?php

namespace App\Repository;

use App\Model\PrivateEvent;

interface PrivateEventRepositoryInterface
{
    /**
     * @throws EntityNotFoundException
     */
    public function findOrFail(int $eventId): PrivateEvent;

    /**
     * @throws EntityNotFoundException
     */
    public function findAll(int $groupId): array;

    public function add(PrivateEvent $privateEvent): void;

    public function update(PrivateEvent $privateEvent): void;

    public function delete(PrivateEvent $privateEvent): void;

}