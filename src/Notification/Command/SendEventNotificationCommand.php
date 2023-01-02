<?php

namespace App\Notification\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Repository\EventRepositoryInterface;
use App\Notification\Handler\NotificationSenderInterface;

class SendEventNotificationEmailsCommand extends Command
{
    protected static $defaultName = 'app:send-notifications';
    private EventRepositoryInterface $eventRepository;
    private NotificationSenderInterface $notificationSender;

    public function __construct(EventRepositoryInterface $eventRepository, NotificationSenderInterface $notificationSender)
    {
        parent::__construct();
        $this->eventRepository = $eventRepository;
        $this->notificationSender = $notificationSender;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $eventAttenders = $this->eventRepository->findUpcommingEventAttenders(30, 5);
        $sentSuccesfully = $this->notificationSender->sendNotifications($eventAttenders);
        return ($sentSuccesfully)? 0 : 1;
    }
}