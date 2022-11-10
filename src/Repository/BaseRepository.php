<?php

namespace App\Repository;

use Doctrine\DBAL\Exception as DbalException;

abstract class BaseRepository
{
    protected const DEFAULT_DATETIME_FORMAT = 'Y-m-d H:i:s.v';
    protected const SQL_ENTITY_NOT_FOUND = '02000';

    /**
     * @throws EntityNotFoundException
     * @throws DbalException\DriverException
     */
    protected function handleDriverException(DbalException\DriverException $e): void
    {
        if ($e->getSQLState() == self::SQL_ENTITY_NOT_FOUND) {
            throw new EntityNotFoundException();
        }
        throw $e;
    }
}