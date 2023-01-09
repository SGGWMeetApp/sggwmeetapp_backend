<?php

namespace App\Repository;

use App\Model\ResetPasswordRequest;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\DriverException;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Persistence\Repository\ResetPasswordRequestRepositoryTrait;

class ResetPasswordRequestRepository extends BaseRepository implements ResetPasswordRequestRepositoryInterface
{
    use ResetPasswordRequestRepositoryTrait;

    public function __construct(
        private readonly Connection $connection,
        private readonly UserRepositoryInterface $userRepository,
        private readonly string $tableName = 'app_owner.password_reset'
    )
    {
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function findResetPasswordRequest(string $selector): ?ResetPasswordRequestInterface
    {
        $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE selector = :selector';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('selector', $selector);
            $result = $statement->executeQuery();
            if ($data = $result->fetchAssociative()) {
                return new ResetPasswordRequest(
                    $this->userRepository->findByIdOrFail($data['user_id']),
                    $data['selector'],
                    $data['hashed_token'],
                    new \DateTime($data['requested_at']),
                    new \DateTime($data['expires_at']
                    )
                );
            } else {
                return null;
            }
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function getMostRecentNonExpiredRequestDate(object $user): ?\DateTimeInterface
    {
        $sql = 'SELECT requested_at FROM ' . $this->tableName . ' WHERE user_id = :user_id ORDER BY requested_at DESC LIMIT 1';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('user_id', $user->getId());
            $result = $statement->executeQuery();
            if($data = $result->fetchAssociative()) {
                return new \DateTime($data['requested_at']);
            } else {
                return null;
            }
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @inheritDoc
     * @param ResetPasswordRequestInterface $resetPasswordRequest
     * @return void
     * @throws DbalException
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws UniqueConstraintViolationException
     */
    public function removeResetPasswordRequest(ResetPasswordRequestInterface $resetPasswordRequest): void
    {
        $this->remove($resetPasswordRequest);
    }

    /**
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws UniqueConstraintViolationException
     * @throws DbalException
     */
    public function add(ResetPasswordRequest $resetPasswordRequest): void
    {
        $sql = 'INSERT INTO ' . $this->tableName . ' (user_id, selector, hashed_token, requested_at, expires_at) VALUES
        (:user_id, :selector, :hashed_token, :requested_at, :expires_at)';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('user_id', $resetPasswordRequest->getUser()->getId());
            $statement->bindValue('selector', $resetPasswordRequest->getSelector());
            $statement->bindValue('hashed_token', $resetPasswordRequest->getHashedToken());
            $statement->bindValue('requested_at',
                $resetPasswordRequest->getRequestedAt()->format(self::DEFAULT_DATETIME_FORMAT)
            );
            $statement->bindValue('expires_at',
                $resetPasswordRequest->getExpiresAt()->format(self::DEFAULT_DATETIME_FORMAT)
            );
            $statement->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @throws DriverException
     * @throws UniqueConstraintViolationException
     * @throws EntityNotFoundException
     * @throws DbalException
     */
    public function persistResetPasswordRequest(ResetPasswordRequestInterface $resetPasswordRequest): void
    {
        $this->add($resetPasswordRequest);
    }

    /**
     * @inheritDoc
     */
    public function getUserIdentifier(object $user): string
    {
        return $user->getAccountData()->getEmail();
    }

    /**
     * @throws UniqueConstraintViolationException
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws DbalException
     */
    public function remove(ResetPasswordRequest $resetPasswordRequest): void
    {
        $sql = 'DELETE FROM ' . $this->tableName . ' WHERE user_id = :user_id';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('user_id', $resetPasswordRequest->getUser()->getId());
            $statement->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    public function createResetPasswordRequest(object $user, \DateTimeInterface $expiresAt, string $selector, string $hashedToken): ResetPasswordRequestInterface
    {
        return new ResetPasswordRequest($user, $selector, $hashedToken, new \DateTime('now'), $expiresAt);
    }

    /**
     * @throws DriverException
     * @throws UniqueConstraintViolationException
     * @throws EntityNotFoundException
     * @throws DbalException
     */
    public function deleteLastRequestForUser(object $user): void
    {
        $tableName = $this->tableName;
        $sql = "DELETE FROM $tableName WHERE id IN (SELECT id FROM $tableName WHERE user_id = :user_id ORDER BY id DESC LIMIT 1)";
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('user_id', $user->getId());
            $statement->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @inheritDoc
     * @return int
     * @throws DbalException
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws UniqueConstraintViolationException
     */
    public function removeExpiredResetPasswordRequests(): int
    {
        $time = new \DateTimeImmutable('-1 week');
        $sql = 'DELETE FROM ' . $this->tableName . ' WHERE requested_at <= :time';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('time', $time->format(self::DEFAULT_DATETIME_FORMAT));
            $result = $statement->executeQuery();
            return $result->rowCount();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }

    }
}
