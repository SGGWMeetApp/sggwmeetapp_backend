<?php

namespace App\EventListener;

use App\Event\AddGroupEventEvent;
use App\Security\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class AddGroupEventListener
{
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function onGroupEventAdd(AddGroupEventEvent $event): void
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
}