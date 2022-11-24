<?php

namespace App\Repository;

use App\Filter\PlaceFilters;
use App\Model\Place;

interface PlaceRepositoryInterface
{
    /**
     * @throws EntityNotFoundException
     */
    public function findOrFail(int $placeId): Place;

    public function findAll(PlaceFilters $filters): array;

    public function add(Place $place): void;

    public function update(Place $place): void;

    public function delete(Place $place): void;

}