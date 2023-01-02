<?php

namespace App\Notification\Handler;

use App\security\User;
use App\Model\AccountData;
use App\Model\Event;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;

class SendEventNotification implements NotificationSenderInterface
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    public function sendNotifications(array $eventAttenders): bool
    {
        $sentSuccesfully = [];
        foreach ($eventAttenders as $key) {
            foreach ($eventAttenders[$key]['attenders'] as $attenders) {
                foreach ($attenders as $attender) {
                    $notification = $this->createNotification(
                        $attender->getAccountData()->getEmail(),
                        $eventAttenders[$key]['event']
                    );
                    $result = $this->sendNotification($notification);
                    $sentSuccesfully[] = $result;
                    if ($result) {
                        $this->logger->info('Notification email for user_id ' . $attender->getId() . ' for event_id ' . $eventAttenders[$key]['event']->getId() . ' was succesfully send');
                    } else {
                        $this->logger->error('Notification email for user_id ' . $attender->getId() . ' for event_id ' . $eventAttenders[$key]['event']->getId() . ' failed');
                    }
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

    private function sendNotification(Email $email): bool
    {
        try {
            $this->mailer->send($email);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Notification error message failed - class SendEventNotification');
            $this->logger->error('ERROR MSG: ' . $e->getMessage());
            return false;
        }
    }
}
