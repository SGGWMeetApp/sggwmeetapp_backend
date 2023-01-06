<?php

namespace App\EventSubscriber;

use App\Event\AddGroupEventEvent;
use App\Event\AppEventEvents;
use App\Event\GroupEvents;
use App\Event\GroupMembershipStatus;
use App\Event\UserGroupMembershipUpdateEvent;
use App\Event\UserJoinedEventEvent;
use App\Model\UserNotificationSettings;
use App\Security\User;
use BenTools\WebPushBundle\Model\Message\PushNotification;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerRegistry;
use BenTools\WebPushBundle\Sender\PushMessageSender;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class NotificationSenderSubscriber implements EventSubscriberInterface
{
    private UserSubscriptionManagerRegistry $userSubscriptionManager;
    private PushMessageSender $pushMessageSender;

    private MailerInterface $mailer;

    private LoggerInterface $logger;

    /**
     * @param UserSubscriptionManagerRegistry $userSubscriptionManager
     * @param PushMessageSender $pushMessageSender
     * @param MailerInterface $mailer
     * @param LoggerInterface $logger
     */
    public function __construct(
        UserSubscriptionManagerRegistry $userSubscriptionManager,
        PushMessageSender               $pushMessageSender,
        MailerInterface                 $mailer,
        LoggerInterface                 $logger
    )
    {
        $this->userSubscriptionManager = $userSubscriptionManager;
        $this->pushMessageSender = $pushMessageSender;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            AppEventEvents::GROUP_EVENT_ADDED => [
                ['onGroupEventAddSendPushNotification', 20],
                ['onGroupEventAddSendEmail', 10]
            ],
            AppEventEvents::USER_JOINED_EVENT => [
                ['onUserJoinedEventSendPushNotification', 20],
                ['onUserJoinedEventSendEmail', 10]
            ],
            GroupEvents::MEMBERSHIP_STATUS_CHANGE => [
                ['onUserGroupMembershipStatusChangeSendPushNotification', 20],
                ['onUserGroupMembershipStatusChangeSendEmail', 10]
            ]
        ];
    }

    public function onGroupEventAddSendPushNotification(AddGroupEventEvent $event): void
    {
        $groupUsers = $event->getPrivateEvent()->getUserGroup()->getUsers();
        foreach ($groupUsers as $groupUser) {
            if(!$event->getPrivateEvent()->getAuthor()->isEqualTo($groupUser) &&
                $groupUser->getNotificationSettings()->getSettingByName(UserNotificationSettings::EVENT_NOTIFICATION)->isEnabled()
            ) {
                $subscriptions = $this->userSubscriptionManager->findByUser($groupUser);
                $notification = new PushNotification('New Event!', [
                    PushNotification::BODY => 'New event is waiting for you in '.
                        $event->getPrivateEvent()->getUserGroup()->getName().' group.'
                ]);
                $responses = [];
                try {
                    $responses = $this->pushMessageSender->push($notification->createMessage(), $subscriptions);
                } catch (\ErrorException $e) {
                    $this->logger->error($e);
                }

                foreach ($responses as $response) {
                    if ($response->isExpired()) {
                        $this->userSubscriptionManager->delete($response->getSubscription());
                    }
                }
            }
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function onGroupEventAddSendEmail(AddGroupEventEvent $event): void
    {
        $groupUsers = $event->getPrivateEvent()->getUserGroup()->getUsers();
        $email = (new TemplatedEmail())
            ->subject('SGGW MeetApp - New event in group '.$event->getPrivateEvent()->getUserGroup()->getName())
            ->htmlTemplate('event/group_event_notification_email.html.twig'
            );
        $commonContext = [
            'event' => $event->getPrivateEvent()
        ];
        /** @var User $groupUser */
        foreach ($groupUsers as $groupUser) {
            if(
                !$event->getPrivateEvent()->getAuthor()->isEqualTo($groupUser) &&
                $groupUser->getNotificationSettings()->getSettingByName(UserNotificationSettings::EVENT_NOTIFICATION)->isEnabled()
            ) {
                $email
                    ->to($groupUser->getAccountData()->getEmail())
                    ->context([
                        'username' => $groupUser->getUserData()->getFirstName().' '.$groupUser->getUserData()->getLastName(),
                        ...$commonContext
                    ]);
                $this->mailer->send($email);
            }
        }
    }

    public function onUserJoinedEventSendPushNotification(UserJoinedEventEvent $joinedEvent): void
    {
        if($joinedEvent->getUser()->isEqualTo($joinedEvent->getEvent()->getAuthor())) {
            return;
        }
        $subscriptions = $this->userSubscriptionManager->findByUser($joinedEvent->getUser());
        $notification = new PushNotification('New user joined your event!', [
            PushNotification::BODY => $joinedEvent->getUser()->getUserData()->getFullName().' has just joined your event '.
                $joinedEvent->getEvent()->getName()
        ]);
        $responses = [];
        try {
            $responses = $this->pushMessageSender->push($notification->createMessage(), $subscriptions);
        } catch (\ErrorException $e) {
            $this->logger->error($e);
        }

        foreach ($responses as $response) {
            if ($response->isExpired()) {
                $this->userSubscriptionManager->delete($response->getSubscription());
            }
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function onUserJoinedEventSendEmail(UserJoinedEventEvent $joinedEvent): void
    {
        if($joinedEvent->getUser()->isEqualTo($joinedEvent->getEvent()->getAuthor())) {
            return;
        }
        $email = (new TemplatedEmail())
            ->to($joinedEvent->getEvent()->getAuthor()->getAccountData()->getEmail())
            ->subject('SGGW MeetApp - New user joined your event '.$joinedEvent->getEvent()->getName())
            ->htmlTemplate('event/event_join_organizer_notification_email.html.twig')
            ->context([
                'username' => $joinedEvent->getEvent()->getAuthor()->getUserData()->getFirstName().
                    ' '.$joinedEvent->getEvent()->getAuthor()->getUserData()->getLastName(),
                'event' => $joinedEvent->getEvent(),
                'joinedUser' => $joinedEvent->getUser()
            ]);
        $this->mailer->send($email);
    }

    public function onUserGroupMembershipStatusChangeSendPushNotification(UserGroupMembershipUpdateEvent $membershipUpdateEvent): void
    {
        $subscriptions = $this->userSubscriptionManager->findByUser($membershipUpdateEvent->getUser());
        $pushMessageContents = $this->getPushNotificationForMembershipUpdateEvent($membershipUpdateEvent);
        $notification = new PushNotification(
            $pushMessageContents['title'], [
                PushNotification::BODY => $pushMessageContents['message']
        ]);
        $responses = [];
        try {
            $responses = $this->pushMessageSender->push($notification->createMessage(), $subscriptions);
        } catch (\ErrorException $e) {
            $this->logger->error($e);
        }
        foreach ($responses as $response) {
            if ($response->isExpired()) {
                $this->userSubscriptionManager->delete($response->getSubscription());
            }
        }
    }

    private function getPushNotificationForMembershipUpdateEvent(UserGroupMembershipUpdateEvent $membershipUpdateEvent): array
    {
        return match ($membershipUpdateEvent->getMembershipStatus()) {
            GroupMembershipStatus::GRANTED => [
                'title' => 'You have a new Group!',
                'message' => $membershipUpdateEvent->getUserGroup()->getOwner()->getUserData()->getFullName() .
                    ' added you to group ' . $membershipUpdateEvent->getUserGroup()->getName()
            ],
            GroupMembershipStatus::REVOKED => [
                'title' => 'You were removed from Group!',
                'message' => $membershipUpdateEvent->getUserGroup()->getOwner()->getUserData()->getFullName() .
                    ' removed you from group ' . $membershipUpdateEvent->getUserGroup()->getName()
            ]
        };
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function onUserGroupMembershipStatusChangeSendEmail(UserGroupMembershipUpdateEvent $membershipUpdateEvent): void
    {
        $email = (new TemplatedEmail())
            ->to($membershipUpdateEvent->getUser()->getAccountData()->getEmail())
            ->subject('SGGW MeetApp - Joined group '.$membershipUpdateEvent->getUserGroup()->getName())
            ->htmlTemplate('user_group/group_membership_update_notification_email.html.twig')
            ->context([
                'username' => $membershipUpdateEvent->getUser()->getUserData()->getFullName(),
                'userGroup' => $membershipUpdateEvent->getUserGroup(),
                'statusChange' => $membershipUpdateEvent->getMembershipStatus()->value
            ]);
        $this->mailer->send($email);
    }
}