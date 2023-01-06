<?php

namespace App\Notification\Handler;

use App\Model\Event;
use App\Security\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Email;
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
        $sentSuccessfully = [];
        if(count($eventAttenders) > 0) {
            $this->logger
                ->info('There are notifications to be sent! Sending notifications to '.count($eventAttenders).
                    ' event attenders.');
        }
        foreach ($eventAttenders as $attenderEventObject) {
            /** @var User $attender */
            foreach ($attenderEventObject['attenders'] as $attender) {
                $notification = $this->createNotification(
                    $attender->getUserData()->getFullName(),
                    $attender->getAccountData()->getEmail(),
                    $attenderEventObject['event']
                );
                $result = $this->sendNotification($notification);
                $sentSuccessfully[] = $result;
                if ($result) {
                    $this->logger->info('Notification email for user_id ' . $attender->getId() . ' for event_id ' . $attenderEventObject['event']->getId() . ' was successfully send');
                } else {
                    $this->logger->error('Notification email for user_id ' . $attender->getId() . ' for event_id ' . $attenderEventObject['event']->getId() . ' failed');
                }
            }
        }
        return !in_array(false, $sentSuccessfully);
    }

    private function createNotification(string $name, string $email, Event $event): Email
    {
        $emailSubject = 'SGGW MeetApp - Upcoming Event Notification';
        return (new TemplatedEmail())
            ->to($email)
            ->subject($emailSubject)
            ->htmlTemplate('event/upcoming_event_notification_email.html.twig')
            ->context([
                'username' => $name,
                'event' => $event
            ]);
    }

    private function sendNotification(Email $email): bool
    {
        try {
            $this->mailer->send($email);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Notification error: message failed - class SendEventNotification');
            $this->logger->error('ERROR MSG: ' . $e->getMessage());
            return false;
        }
    }
}
