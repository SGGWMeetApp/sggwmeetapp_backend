<?php

namespace App\EventSubscriber;

use App\Event\AddGroupEventEvent;
use App\Event\AppEventEvents;
use App\Event\UserJoinedEventEvent;
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
            ]
        ];
    }

    public function onGroupEventAddSendPushNotification(AddGroupEventEvent $event): void
    {
        $groupUsers = $event->getPrivateEvent()->getUserGroup()->getUsers();
        foreach ($groupUsers as $groupUser) {
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
            if(!$event->getPrivateEvent()->getAuthor()->isEqualTo($groupUser)) {
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
}