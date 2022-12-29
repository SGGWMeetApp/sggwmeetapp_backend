<?php

namespace App\EventListener;

use App\Event\UserJoinedEventEvent;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class UserJoinedEventListener
{

    /**
     * @param MailerInterface $mailer
     */
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function onUserJoinedEvent(UserJoinedEventEvent $joinedEvent): void
    {
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