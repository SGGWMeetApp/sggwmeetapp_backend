<?php

namespace App\Notification\Handler;

use App\security\User;
use App\Model\AccountData;
use App\Model\Event;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;

class SendEventNotification implements NotificationSenderInterface
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendNotifications(array $eventAttenders): bool
    {
        $sentSuccesfully = [];
        foreach ($eventAttenders as $key) {
            foreach ($eventAttenders[$key]['attenders'] as $attenders) {
                foreach($attenders as $attender) {
                    $notification = $this->createNotification(
                        $attender->getAccountData()->getEmail(), 
                        $eventAttenders[$key]['event']
                    );
                    $sentSuccesfully[] = $this->sendNotification($notification);
                }
            }
        }
        return array_key_exists(false, $sentSuccesfully);
    }

    private function createNotification(string $email, Event $event): Email
    {
        $emailTitle = 'Upcomming event for you';
        return (new Email())
            ->from(new Address('123@example.com', 'SGGW Meet App'))
            ->to($email)
            ->subject($emailTitle)
            ->text($event->getName());
    }

    private function sendNotification (Email $email): bool
    {
        try {
            $this->mailer->send($email);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}