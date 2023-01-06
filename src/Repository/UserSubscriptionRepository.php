<?php

namespace App\Repository;

use App\Model\UserSubscription;
use App\Serializer\UserSubscriptionNormalizer;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class UserSubscriptionRepository extends BaseRepository implements UserSubscriptionManagerInterface
{
    private Connection $connection;
    private UserSubscriptionNormalizer $subscriptionNormalizer;
    private string $tableName = 'app_owner.user_subscriptions';

    /**
     * @param Connection $connection
     * @param UserSubscriptionNormalizer $subscriptionNormalizer
     */
    public function __construct(Connection $connection, UserSubscriptionNormalizer $subscriptionNormalizer)
    {
        $this->connection = $connection;
        $this->subscriptionNormalizer = $subscriptionNormalizer;
    }

    /**
     * @inheritDoc
     */
    public function factory(UserInterface $user, string $subscriptionHash, array $subscription, array $options = []): UserSubscriptionInterface
    {
        return new UserSubscription($user, $subscriptionHash, $subscription);
    }

    /**
     * @inheritDoc
     */
    public function hash(string $endpoint, UserInterface $user): string
    {
        return md5($endpoint);
    }

    /**
     * @inheritDoc
     * @param UserInterface $user
     * @param string $subscriptionHash
     * @return UserSubscriptionInterface|null
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws Exception
     * @throws UniqueConstraintViolationException
     * @throws ExceptionInterface
     */
    public function getUserSubscription(UserInterface $user, string $subscriptionHash): ?UserSubscriptionInterface
    {
        $sql = 'SELECT * FROM '.$this->tableName.' WHERE user_id=:user_id AND subscription_hash=:subscription_hash';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('user_id', $user->getId());
            $statement->bindValue('subscription_hash', $subscriptionHash);
            $result = $statement->executeQuery();
            $data = $result->fetchAssociative();
            if ($data) {
                return $this->subscriptionNormalizer->denormalize($data, UserSubscription::class);
            }
            return null;
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @inheritDoc
     * @param UserInterface $user
     * @return iterable
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws Exception
     * @throws ExceptionInterface
     * @throws UniqueConstraintViolationException
     */
    public function findByUser(UserInterface $user): iterable
    {
        $sql = 'SELECT * FROM '.$this->tableName.' WHERE user_id=:user_id';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('user_id', $user->getId());
            $result = $statement->executeQuery();
            $subscriptions = [];
            while ($data = $result->fetchAssociative()) {
                $subscriptions [] = $this->subscriptionNormalizer->denormalize($data, UserSubscription::class);
            }
            return $subscriptions;
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @inheritDoc
     * @param string $subscriptionHash
     * @return iterable
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws Exception
     * @throws ExceptionInterface
     * @throws UniqueConstraintViolationException
     */
    public function findByHash(string $subscriptionHash): iterable
    {
        $sql = 'SELECT * FROM '.$this->tableName.' WHERE subscription_hash=:subscription_hash';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('subscription_hash', $subscriptionHash);
            $result = $statement->executeQuery();
            $subscriptions = [];
            while ($data = $result->fetchAssociative()) {
                $subscriptions [] = $this->subscriptionNormalizer->denormalize($data, UserSubscription::class);
            }
            return $subscriptions;
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @inheritDoc
     * @param UserSubscriptionInterface $userSubscription
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws Exception
     * @throws UniqueConstraintViolationException
     */
    public function save(UserSubscriptionInterface $userSubscription): void
    {
        $sql = 'INSERT INTO '.$this->tableName.' (user_id, subscription_hash, subscription) VALUES (:user_id, :sub_hash, :sub)';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('user_id', $userSubscription->getUser()->getId());
            $statement->bindValue('sub_hash', $userSubscription->getSubscriptionHash());
            $statement->bindValue('sub', $userSubscription->getSubscription());
            $statement->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }
    }

    /**
     * @inheritDoc
     * @param UserSubscriptionInterface $userSubscription
     * @throws DriverException
     * @throws EntityNotFoundException
     * @throws Exception
     * @throws UniqueConstraintViolationException
     */
    public function delete(UserSubscriptionInterface $userSubscription): void
    {
        $sql = 'DELETE FROM '.$this->tableName.' WHERE user_id=:user_id AND subscription_hash=:sub_hash';
        try {
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('user_id', $userSubscription->getUser()->getId());
            $statement->bindValue('sub_hash', $userSubscription->getSubscriptionHash());
            $statement->executeQuery();
        } catch (DriverException $e) {
            $this->handleDriverException($e);
        }

    }
}