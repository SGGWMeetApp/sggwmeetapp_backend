<?php

namespace App\Repository;

use App\Model\PublicEvent;
use App\Security\User;

interface PublicEventRepositoryInterface
{
     /**
     * @throws EntityNotFoundException
     */
    public function findOrFail(int $eventId): PublicEvent;

    public function findAll(): array;

    public function add(PublicEvent $publicEvent): void;

    public function update(PublicEvent $publicEvent): void;

    public function delete(PublicEvent $publicEvent): void;
    
}