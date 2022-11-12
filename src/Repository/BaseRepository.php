<?php

namespace App\Repository;

use Doctrine\DBAL\Exception as DbalException;

abstract class BaseRepository
{
    protected const DEFAULT_DATETIME_FORMAT = 'Y-m-d H:i:s.v';
    protected const SQL_ENTITY_NOT_FOUND = '02000';
    protected const SQL_UNIQUE_CONSTRAINT_VIOLATION = '23505';

    /**
     * @throws EntityNotFoundException
     * @throws DbalException\DriverException
     * @throws UniqueConstraintViolationException
     */
    protected function handleDriverException(DbalException\DriverException $e): void
    {
        if ($e->getSQLState() == self::SQL_ENTITY_NOT_FOUND) {
            throw new EntityNotFoundException();
        }
        if ($e->getSQLState() == self::SQL_UNIQUE_CONSTRAINT_VIOLATION) {
            $matches = [];
            preg_match('/\"(\w+)\"/', $e->getMessage(), $matches);
            if(count($matches) > 1) {
                throw new UniqueConstraintViolationException($matches[1]);
            } else {
                throw new UniqueConstraintViolationException('<<unknown>>');
            }

        }
        throw $e;
    }
}